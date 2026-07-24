<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Curation;
use App\Models\CurationPlace;
use App\Models\Place;
use App\Models\PlaceImage;
use App\Models\Theme;
use App\Services\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CurationController extends Controller
{
    public function show(int $id)
    {
        $curation = Curation::where('id', $id)
            ->where(function ($q) {
                $q->where('status', 'approved')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'pending')->whereNotNull('approved_snapshot');
                  });
            })
            ->firstOrFail();

        $viewKey = "curation_view:{$curation->id}:" . (request()->ip() ?? 'unknown');
        if (!Cache::has($viewKey)) {
            $curation->increment('view_count');
            Cache::put($viewKey, true, 300);
        }

        $curation->load(['places', 'author']);

        if ($curation->status === 'pending' && $curation->approved_snapshot) {
            $snap = $curation->approved_snapshot;
            $curation->title = $snap['title'] ?? $curation->title;
            $curation->description = $snap['description'] ?? $curation->description;
            $curation->category = $snap['category'] ?? $curation->category;
            $curation->region_label = $snap['region_label'] ?? $curation->region_label;
            $curation->cover_image = $snap['cover_image'] ?? $curation->cover_image;

            if (!empty($snap['places'])) {
                $snapPlaces = collect($snap['places'])->map(fn($p) => new CurationPlace($p));
                $curation->setRelation('places', $snapPlaces);
            }
        }

        $userCategories = [];
        if (Auth::check()) {
            $userCategories = Category::where('user_id', Auth::id())
                ->orderBy('sort_order')
                ->get(['id', 'name', 'icon']);
        }

        return view('curations.show', compact('curation', 'userCategories'));
    }

    public function saveToMyPinpick(Request $request, int $id)
    {
        $curation = Curation::where('id', $id)->published()->firstOrFail();

        $request->validate([
            'place_ids' => 'required|array|min:1',
            'place_ids.*' => 'integer|exists:curation_places,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'new_category_name' => 'nullable|string|max:30',
        ]);

        $user = Auth::user();

        if ($request->new_category_name) {
            $category = Category::where('user_id', $user->id)
                ->where('name', $request->new_category_name)
                ->first();
            if (!$category) {
                Category::where('user_id', $user->id)->increment('sort_order');
                $category = Category::create([
                    'user_id' => $user->id,
                    'name' => $request->new_category_name,
                    'icon' => '📌',
                    'sort_order' => 0,
                ]);
            }
        } elseif ($request->category_id) {
            $category = Category::where('id', $request->category_id)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            return response()->json(['error' => '카테고리를 선택해주세요.'], 422);
        }

        $curPlaces = CurationPlace::where('curation_id', $curation->id)
            ->whereIn('id', $request->place_ids)
            ->get();

        $existingExtIds = Place::where('user_id', $user->id)
            ->whereNotNull('kakao_place_id')
            ->pluck('kakao_place_id')
            ->merge(
                Place::where('user_id', $user->id)
                    ->whereNotNull('naver_place_id')
                    ->pluck('naver_place_id')
            )
            ->toArray();

        $maxSort = Place::where('user_id', $user->id)->max('sort_order') ?? -1;
        $saved = 0;
        $skipped = 0;

        $koreaProvinces = ['서울','부산','대구','인천','광주','대전','울산','세종','경기','강원','충북','충남','전북','전남','경북','경남','제주'];
        $isCourse = $curation->type === 'course';

        foreach ($curPlaces as $cp) {
            $isDup = ($cp->external_place_id && in_array($cp->external_place_id, $existingExtIds))
                || ($cp->naver_place_id && in_array($cp->naver_place_id, $existingExtIds));
            if ($isDup) {
                $skipped++;
                continue;
            }

            $isOverseas = (bool) $cp->is_overseas;
            if (!$isOverseas && $cp->address) {
                $isOverseas = true;
                foreach ($koreaProvinces as $prov) {
                    if (str_starts_with($cp->address, $prov)) {
                        $isOverseas = false;
                        break;
                    }
                }
            }

            $memo = '';
            if ($isCourse && $cp->day_number) {
                $memo = "Day {$cp->day_number}";
            }

            $newPlace = Place::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'name' => $cp->place_name,
                'original_name' => $cp->place_name,
                'address' => $cp->jibeon_address,
                'road_address' => $cp->address,
                'building_name' => $cp->building_name,
                'phone' => $cp->phone,
                'opening_hours' => $cp->opening_hours,
                'lat' => $cp->latitude,
                'lng' => $cp->longitude,
                'memo' => $memo,
                'status' => 'planned',
                'is_overseas' => $isOverseas,
                'is_public' => false,
                'sort_order' => ++$maxSort,
                'kakao_place_id' => $cp->external_place_id,
                'naver_place_id' => $cp->naver_place_id,
                'google_place_id' => $cp->google_place_id,
            ]);

            if (!empty($cp->photos)) {
                foreach ($cp->photos as $i => $photoPath) {
                    if (Storage::disk('public')->exists($photoPath)) {
                        PlaceImage::create([
                            'place_id' => $newPlace->id,
                            'path' => $photoPath,
                            'sort_order' => $i,
                        ]);
                    }
                }
                $firstPhoto = $cp->photos[0];
                $thumbPath = ImageProcessor::thumbPathFor($firstPhoto);
                if (Storage::disk('public')->exists($thumbPath)) {
                    $newPlace->update(['thumbnail' => $thumbPath]);
                } elseif (Storage::disk('public')->exists($firstPhoto)) {
                    $newPlace->update(['thumbnail' => $firstPhoto]);
                }
            }

            $saved++;
        }

        if ($saved > 0) {
            $curation->increment('save_count');
        }

        return response()->json([
            'success' => true,
            'saved' => $saved,
            'skipped' => $skipped,
        ]);
    }

    public function apiList(Request $request)
    {
        $category = $request->get('category');
        $curations = Curation::where(function ($q) {
                $q->where('status', 'approved')
                  ->orWhere(fn($q2) => $q2->where('status', 'pending')->whereNotNull('approved_snapshot'));
            })
            ->with(['places' => fn($q) => $q->orderBy('sort_order')->limit(6), 'author:id,name,profile_image'])
            ->withCount('places')
            ->when($category, fn($q) => $q->where('category', $category))
            ->orderByDesc('published_at')
            ->limit(50)
            ->get(['id', 'title', 'slug', 'type', 'category', 'description',
                    'cover_image', 'save_count', 'published_at',
                    'author_type', 'author_user_id', 'status', 'approved_snapshot']);

        $savedIds = [];
        if (Auth::check()) {
            $savedIds = \App\Models\Place::where('user_id', Auth::id())
                ->whereNotNull('kakao_place_id')
                ->pluck('kakao_place_id')
                ->merge(
                    \App\Models\Place::where('user_id', Auth::id())
                        ->whereNotNull('naver_place_id')
                        ->pluck('naver_place_id')
                )
                ->toArray();
        }

        $curations->each(function ($c) use ($savedIds) {
            if ($c->status === 'pending' && $c->approved_snapshot) {
                $snap = $c->approved_snapshot;
                $c->title = $snap['title'] ?? $c->title;
                $c->description = $snap['description'] ?? $c->description;
                $c->category = $snap['category'] ?? $c->category;
                $c->cover_image = $snap['cover_image'] ?? $c->cover_image;
                if (!empty($snap['places'])) {
                    $snapPlaces = collect($snap['places'])->map(fn($p) => new CurationPlace($p));
                    $c->setRelation('places', $snapPlaces);
                    $c->places_count = count($snap['places']);
                }
            }

            $coverUrl = $c->cover_image ? asset('storage/' . $c->cover_image) : null;
            $c->cover_url = $coverUrl;
            $catConfig = config("curation_categories.{$c->category}");
            $c->category_label = $catConfig['label'] ?? $c->category;

            $hasSaved = false;
            $c->places_list = $c->places->map(function ($p) use (&$hasSaved, $savedIds) {
                $thumb = $p->thumb_url;
                if (!empty($savedIds)) {
                    if (($p->external_place_id && in_array($p->external_place_id, $savedIds))
                        || ($p->naver_place_id && in_array($p->naver_place_id, $savedIds))) {
                        $hasSaved = true;
                    }
                }
                return [
                    'id' => $p->id,
                    'name' => $p->place_name,
                    'address' => $p->address,
                    'category_label' => $p->category_label,
                    'thumb_url' => $thumb,
                    'region' => $p->address ? mb_substr(explode(' ', $p->address)[0] ?? '', 0, 10) : '',
                ];
            });
            $c->is_saved = $hasSaved;
            $c->author_name = $c->author_type === 'user' && $c->author
                ? $c->author->name : '핀픽';
            $c->author_avatar = $c->author_type === 'user' && $c->author && $c->author->profile_image
                ? $c->author->profile_image : null;
            $c->is_official = $c->author_type === 'admin';
            unset($c->places, $c->author, $c->status, $c->approved_snapshot);
        });

        return response()->json($curations);
    }

    public function apiCategories()
    {
        $usedCategories = Curation::where(function ($q) {
                $q->where('status', 'approved')
                  ->orWhere(fn($q2) => $q2->where('status', 'pending')->whereNotNull('approved_snapshot'));
            })
            ->select('category')
            ->groupBy('category')
            ->pluck('category')
            ->toArray();

        $all = config('curation_categories');
        $result = [];
        foreach ($all as $slug => $cat) {
            if (in_array($slug, $usedCategories)) {
                $result[] = ['slug' => $slug, 'label' => $cat['label'], 'icon' => $cat['icon']];
            }
        }

        return response()->json($result);
    }
}
