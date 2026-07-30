<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    public function __construct(private AvatarService $avatarService) {}

    public function index()
    {
        $personas = User::personas()->latest()->get();
        return view('admin.personas.index', compact('personas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:8',
            'bio' => 'nullable|string|max:100',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'bio' => $data['bio'] ?? null,
            'is_operator_persona' => true,
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('personas', 'public');
            $user->update(['profile_image' => asset('storage/' . $path)]);
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
        ]);

        $persona->update([
            'name' => $data['name'],
            'bio' => $data['bio'] ?? null,
        ]);

        if ($request->hasFile('avatar')) {
            $this->avatarService->deleteOldAvatar($persona->profile_image);
            $path = $request->file('avatar')->store('personas', 'public');
            $persona->update(['profile_image' => asset('storage/' . $path)]);
        }

        return back()->with('success', '수정 완료');
    }

    public function destroy(User $persona)
    {
        if (!$persona->is_operator_persona) abort(404);
        $this->avatarService->deleteOldAvatar($persona->profile_image);
        $persona->delete();
        return back()->with('success', '삭제 완료');
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

    public function seed()
    {
        $defaults = [
            ['분당토박이', '분당에서 20년, 동네 맛집만 팝니다'],
            ['미금동밥집', '미금역 반경 도보 15분 전문'],
            ['판교직장인', '점심 1시간의 승부사'],
            ['야탑먹거리', '야탑·서현 위주로 다녀요'],
            ['수내커피', '카페는 분위기 반 커피 반'],
            ['주말나들이', '토요일마다 수도권 어딘가'],
            ['아이랑출동', '애 데리고 갈 만한 곳만 저장'],
            ['둘이서한바퀴', '데이트 코스 수집 중'],
            ['혼밥독립군', '혼자 가도 눈치 안 보이는 집'],
            ['제주살이중', '제주 구석구석 기록'],
            ['전국맛지도', '출장 다니며 찍은 진짜배기'],
            ['골목탐험가', '프랜차이즈 말고 골목집'],
        ];

        $created = 0;
        $skipped = 0;
        $existing = User::personas()->pluck('name')->toArray();

        foreach ($defaults as [$name, $bio]) {
            if (in_array($name, $existing)) {
                $skipped++;
                continue;
            }
            $user = User::create([
                'name' => $name,
                'bio' => $bio,
                'is_operator_persona' => true,
            ]);

            $url = $this->avatarService->generateForPersona($name);
            if ($url) {
                $user->update(['profile_image' => $url]);
            }

            $created++;
        }

        return back()->with('success', "{$created}개 생성, {$skipped}개 스킵");
    }
}
