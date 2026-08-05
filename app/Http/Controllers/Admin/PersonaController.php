<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PersonaController extends Controller
{
    public function __construct(private AvatarService $avatarService) {}

    public function index()
    {
        $personas = User::personas()->latest()->get();
        $excluded = DB::table('persona_excluded_nicknames')
            ->orderByDesc('excluded_at')->get();
        $targetCount = $this->getTargetCount();
        $topicRatio = $this->getTopicRatio();
        $poolRemaining = $this->availablePoolCount();
        $poolRemainingByScope = $this->availablePoolCountByScope();

        return view('admin.personas.index', compact(
            'personas', 'excluded', 'targetCount', 'topicRatio',
            'poolRemaining', 'poolRemainingByScope'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:8',
            'bio' => 'nullable|string|max:100',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'persona_scope' => 'nullable|in:topic,region',
            'persona_region_tag' => 'nullable|string|max:20',
        ]);

        $scope = $data['persona_scope'] ?? 'topic';

        $user = User::create([
            'name' => $data['name'],
            'bio' => $data['bio'] ?? null,
            'is_operator_persona' => true,
            'persona_scope' => $scope,
            'persona_region_tag' => $scope === 'region' ? ($data['persona_region_tag'] ?? null) : null,
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('personas', 'public');
            $user->update(['profile_image' => rtrim(config('app.url'), '/') . '/storage/' . $path]);
        } else {
            $url = $this->avatarService->generateForPersona($data['name']);
            if ($url) {
                $user->update(['profile_image' => $url]);
            }
        }

        return back()->with('success', "페르소나 '{$data['name']}' 생성 완료");
    }

    public function update(Request $request, User $persona)
    {
        if (!$persona->is_operator_persona) abort(404);

        $data = $request->validate([
            'name' => 'required|string|min:2|max:8',
            'bio' => 'nullable|string|max:100',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'persona_scope' => 'nullable|in:topic,region',
            'persona_region_tag' => 'nullable|string|max:20',
        ]);

        $scope = $data['persona_scope'] ?? $persona->persona_scope ?? 'topic';

        $persona->update([
            'name' => $data['name'],
            'bio' => $data['bio'] ?? null,
            'persona_scope' => $scope,
            'persona_region_tag' => $scope === 'region'
                ? ($data['persona_region_tag'] ?? $persona->persona_region_tag) : null,
        ]);

        if ($request->hasFile('avatar')) {
            $this->avatarService->deleteOldAvatar($persona->profile_image);
            $path = $request->file('avatar')->store('personas', 'public');
            $persona->update(['profile_image' => url('storage/' . $path)]);
        }

        return back()->with('success', '수정 완료');
    }

    public function destroy(User $persona)
    {
        if (!$persona->is_operator_persona) abort(404);

        DB::table('persona_excluded_nicknames')->insertOrIgnore([
            'nickname' => $persona->name,
            'excluded_at' => now(),
        ]);

        $this->avatarService->deleteOldAvatar($persona->profile_image);
        $persona->delete();

        return back()->with('success', "'{$persona->name}' 삭제 (제외 이력에 기록됨)");
    }

    public function restoreExcluded(Request $request)
    {
        $id = $request->input('id');
        $row = DB::table('persona_excluded_nicknames')->where('id', $id)->first();
        if (!$row) return back()->with('error', '이력을 찾을 수 없습니다');

        DB::table('persona_excluded_nicknames')->where('id', $id)->delete();

        return back()->with('success', "'{$row->nickname}' 제외 이력 해제");
    }

    public function regenerateAvatar(User $persona)
    {
        if (!$persona->is_operator_persona) abort(404);

        $this->avatarService->deleteOldAvatar($persona->profile_image);
        $suffix = substr(md5(microtime(true)), 0, 6);
        $url = $this->avatarService->generateForPersona($persona->name, $suffix);

        if ($url) {
            $persona->update(['profile_image' => $url]);
            return back()->with('success', "'{$persona->name}' 아바타 재생성 완료");
        }

        return back()->with('error', '아바타 생성 실패 — DiceBear API 응답 오류');
    }

    public function regenerateAllAvatars()
    {
        $personas = User::personas()->get();
        $success = 0;
        $failed = 0;

        foreach ($personas as $persona) {
            $this->avatarService->deleteOldAvatar($persona->profile_image);
            $suffix = substr(md5($persona->id . microtime(true)), 0, 6);
            $url = $this->avatarService->generateForPersona($persona->name, $suffix);

            if ($url) {
                $persona->update(['profile_image' => $url]);
                $success++;
            } else {
                $failed++;
            }
        }

        $msg = "{$success}개 아바타 재생성 완료";
        if ($failed) $msg .= ", {$failed}개 실패";

        return back()->with('success', $msg);
    }

    public function updateTargetCount(Request $request)
    {
        $count = $request->validate(['target_count' => 'required|integer|min:1|max:50'])['target_count'];

        file_put_contents(storage_path('app/persona_target_count.txt'), $count);

        return back()->with('success', "목표 개수를 {$count}개로 변경");
    }

    public function updateTopicRatio(Request $request)
    {
        $ratio = $request->validate(['topic_ratio' => 'required|integer|min:0|max:100'])['topic_ratio'];

        file_put_contents(storage_path('app/persona_topic_ratio.txt'), $ratio);

        return back()->with('success', "주제형 비율을 {$ratio}%로 변경");
    }

    public function seed()
    {
        $targetCount = $this->getTargetCount();
        $currentCount = User::personas()->count();

        if ($currentCount >= $targetCount) {
            return back()->with('success', "이미 목표({$targetCount}개)에 도달했습니다 (현재 {$currentCount}개)");
        }

        $need = $targetCount - $currentCount;
        $topicRatio = $this->getTopicRatio();

        $topicNeed = (int) round($need * $topicRatio / 100);
        $regionNeed = $need - $topicNeed;

        $excluded = $this->getExcludedNames();
        $pool = config('persona_pool.candidates', []);

        $topicPool = collect($pool)
            ->filter(fn($item) => ($item[2] ?? 'topic') === 'topic' && !in_array($item[0], $excluded))
            ->values();
        $regionPool = collect($pool)
            ->filter(fn($item) => ($item[2] ?? 'topic') === 'region' && !in_array($item[0], $excluded))
            ->values();

        $topicPick = $topicPool->shuffle()->take($topicNeed);
        $regionPick = $regionPool->shuffle()->take($regionNeed);

        if ($topicPick->count() < $topicNeed) {
            $deficit = $topicNeed - $topicPick->count();
            $extraRegion = $regionPool->diff($regionPick)->shuffle()->take($deficit);
            $regionPick = $regionPick->merge($extraRegion);
        }
        if ($regionPick->count() < $regionNeed) {
            $deficit = $regionNeed - $regionPick->count();
            $extraTopic = $topicPool->diff($topicPick)->shuffle()->take($deficit);
            $topicPick = $topicPick->merge($extraTopic);
        }

        $allPick = $topicPick->merge($regionPick);

        if ($allPick->isEmpty()) {
            return back()->with('error', '추천 후보가 부족합니다. AI 생성을 이용하세요.');
        }

        $created = 0;
        foreach ($allPick as $item) {
            $user = User::create([
                'name' => $item[0],
                'bio' => $item[1],
                'is_operator_persona' => true,
                'persona_scope' => $item[2] ?? 'topic',
                'persona_region_tag' => $item[3] ?? null,
            ]);

            $url = $this->avatarService->generateForPersona($item[0]);
            if ($url) {
                $user->update(['profile_image' => $url]);
            }
            $created++;
        }

        $remaining = $this->availablePoolCount();
        $total = $currentCount + $created;
        $tCount = $topicPick->count();
        $rCount = $regionPick->count();
        $msg = "{$created}개 생성 (주제 {$tCount} + 지역 {$rCount}, 현재 {$total}개)";
        if ($remaining === 0) {
            $msg .= ' — 후보 풀이 소진되었습니다';
        }

        return back()->with('success', $msg);
    }

    public function generateAi(Request $request)
    {
        $data = $request->validate([
            'count' => 'required|integer|min:1|max:10',
            'scope' => 'nullable|in:topic,region,mixed',
        ]);
        $count = $data['count'];
        $scope = $data['scope'] ?? 'mixed';

        $existingNames = User::personas()->pluck('name')->toArray();
        $excludedNames = DB::table('persona_excluded_nicknames')->pluck('nickname')->toArray();
        $allExcluded = array_unique(array_merge($existingNames, $excludedNames));

        $apiKey = config('services.anthropic.api_key');
        if (!$apiKey) {
            return response()->json(['error' => 'Anthropic API 키가 설정되지 않았습니다'], 500);
        }

        $excludeList = implode(', ', $allExcluded);

        $scopeInstruction = match ($scope) {
            'topic' => '모두 주제형(맛집/카페/여행/나들이 등 성향 기반)으로 생성하세요. 닉네임에 지역명을 넣지 마세요.',
            'region' => '모두 지역형(특정 지역 기반)으로 생성하세요. 닉네임에 지역명을 포함하세요. region_tag에 해당 지역 대분류(수도권/부산·경남/대구·경북/광주·전라/대전·충청/강원/제주)를 넣어주세요.',
            default => '주제형과 지역형을 섞어서 생성하세요.',
        };

        $prompt = <<<PROMPT
한국의 장소 저장 서비스 '핀픽'에 사용할 페르소나 닉네임과 소개글을 {$count}개 생성해주세요.

조건:
- 닉네임: 2~8자, 한글, 실제 사용자처럼 자연스러운 닉네임
- 소개: 15~30자, 과장이나 홍보성 문구 없이 담백하게
- {$scopeInstruction}
- 다음 닉네임은 이미 사용 중이니 절대 중복하지 마세요: {$excludeList}

응답 형식 (JSON 배열만, 설명 없이):
[{"name": "닉네임", "bio": "소개 한 줄", "scope": "topic 또는 region", "region_tag": "지역명 또는 null"}, ...]
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (!$response->successful()) {
                return response()->json(['error' => 'API 호출 실패: ' . $response->status()], 500);
            }

            $body = $response->json();
            $text = $body['content'][0]['text'] ?? '';

            if (preg_match('/\[.*\]/s', $text, $m)) {
                $candidates = json_decode($m[0], true);
                if (is_array($candidates)) {
                    $filtered = collect($candidates)->filter(function ($c) use ($allExcluded) {
                        return !empty($c['name'])
                            && !empty($c['bio'])
                            && mb_strlen($c['name']) >= 2
                            && mb_strlen($c['name']) <= 8
                            && !in_array($c['name'], $allExcluded);
                    })->map(function ($c) {
                        return [
                            'name' => $c['name'],
                            'bio' => $c['bio'],
                            'scope' => $c['scope'] ?? 'topic',
                            'region_tag' => $c['region_tag'] ?? null,
                        ];
                    })->values();

                    return response()->json(['candidates' => $filtered]);
                }
            }

            return response()->json(['error' => 'AI 응답 파싱 실패'], 500);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'API 오류: ' . $e->getMessage()], 500);
        }
    }

    public function storeAiCandidates(Request $request)
    {
        $items = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|min:2|max:8',
            'items.*.bio' => 'required|string|max:100',
            'items.*.scope' => 'nullable|in:topic,region',
            'items.*.region_tag' => 'nullable|string|max:20',
        ])['items'];

        $created = 0;
        $existing = User::personas()->pluck('name')->toArray();

        foreach ($items as $item) {
            if (in_array($item['name'], $existing)) continue;

            $scope = $item['scope'] ?? 'topic';

            $user = User::create([
                'name' => $item['name'],
                'bio' => $item['bio'],
                'is_operator_persona' => true,
                'persona_scope' => $scope,
                'persona_region_tag' => $scope === 'region' ? ($item['region_tag'] ?? null) : null,
            ]);

            $url = $this->avatarService->generateForPersona($item['name']);
            if ($url) {
                $user->update(['profile_image' => $url]);
            }

            $existing[] = $item['name'];
            $created++;
        }

        return back()->with('success', "AI 페르소나 {$created}개 생성 완료");
    }

    private function getTargetCount(): int
    {
        $file = storage_path('app/persona_target_count.txt');
        if (file_exists($file)) {
            $val = (int) trim(file_get_contents($file));
            if ($val >= 1 && $val <= 50) return $val;
        }
        return config('persona_pool.target_count', 12);
    }

    private function getTopicRatio(): int
    {
        $file = storage_path('app/persona_topic_ratio.txt');
        if (file_exists($file)) {
            $val = (int) trim(file_get_contents($file));
            if ($val >= 0 && $val <= 100) return $val;
        }
        return config('persona_pool.topic_ratio', 70);
    }

    private function getExcludedNames(): array
    {
        $active = User::personas()->pluck('name')->toArray();
        $history = DB::table('persona_excluded_nicknames')->pluck('nickname')->toArray();
        return array_unique(array_merge($active, $history));
    }

    private function getAvailablePool(?string $scope = null): array
    {
        $excluded = $this->getExcludedNames();
        $pool = config('persona_pool.candidates', []);

        return collect($pool)
            ->filter(function ($item) use ($excluded, $scope) {
                if (in_array($item[0], $excluded)) return false;
                if ($scope !== null && ($item[2] ?? 'topic') !== $scope) return false;
                return true;
            })
            ->values()
            ->toArray();
    }

    private function availablePoolCount(): int
    {
        return count($this->getAvailablePool());
    }

    private function availablePoolCountByScope(): array
    {
        return [
            'topic' => count($this->getAvailablePool('topic')),
            'region' => count($this->getAvailablePool('region')),
        ];
    }
}
