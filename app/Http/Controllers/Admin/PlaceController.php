<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Services\AddressParserService;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Place::with(['category', 'user']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('road_address', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $places = $query->latest()->paginate(20)->appends($request->query());

        return view('admin.places.index', compact('places'));
    }

    public function show(Place $place)
    {
        $place->load(['category', 'user', 'images', 'themes']);
        return view('admin.places.show', compact('place'));
    }

    public function destroy(Place $place)
    {
        $place->delete();
        return redirect()->route('admin.places.index')->with('success', '장소가 삭제되었습니다.');
    }

    public function similarPlaces(Place $place)
    {
        if (!$place->lat || !$place->lng) {
            return response()->json(['similar' => []]);
        }

        $similar = Place::whereNull('deleted_at')
            ->where('id', '!=', $place->id)
            ->where('name', $place->name)
            ->whereNotNull('lat')->whereNotNull('lng')
            ->get()
            ->filter(function ($p) use ($place) {
                $dlat = abs($p->lat - $place->lat);
                $dlng = abs($p->lng - $place->lng);
                return $dlat < 0.0005 && $dlng < 0.0005;
            })
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'address' => $p->road_address ?: $p->address,
                'country_code' => $p->country_code,
                'region_l1' => $p->region_l1,
                'user_name' => $p->user?->name ?? '(삭제됨)',
            ])
            ->values();

        return response()->json(['similar' => $similar]);
    }

    public function updateRegion(Request $request, Place $place)
    {
        $data = $request->validate([
            'country_code' => 'nullable|string|max:2',
            'region_l1' => 'nullable|string|max:50',
            'region_l2' => 'nullable|string|max:50',
            'region_l1_key' => 'nullable|string|max:80',
            'region_l2_key' => 'nullable|string|max:80',
            'apply_similar' => 'nullable|boolean',
        ]);

        $l1Key = !empty($data['region_l1_key'])
            ? $data['region_l1_key']
            : AddressParserService::normalizeKey($data['region_l1'] ?? null);
        $l2Key = !empty($data['region_l2_key'])
            ? $data['region_l2_key']
            : AddressParserService::normalizeKey($data['region_l2'] ?? null);

        $updateData = [
            'country_code' => $data['country_code'] ?: null,
            'region_l1' => $data['region_l1'] ?: null,
            'region_l2' => $data['region_l2'] ?: null,
            'region_l1_key' => $l1Key,
            'region_l2_key' => $l2Key,
        ];

        $place->update($updateData);
        $updated = [$place->id];

        if (!empty($data['apply_similar']) && $place->lat && $place->lng) {
            $similar = Place::whereNull('deleted_at')
                ->where('id', '!=', $place->id)
                ->where('name', $place->name)
                ->whereNotNull('lat')->whereNotNull('lng')
                ->get()
                ->filter(function ($p) use ($place) {
                    $dlat = abs($p->lat - $place->lat);
                    $dlng = abs($p->lng - $place->lng);
                    return $dlat < 0.0005 && $dlng < 0.0005;
                });

            foreach ($similar as $s) {
                $s->update($updateData);
                $updated[] = $s->id;
            }
        }

        return response()->json(['success' => true, 'updated_ids' => $updated]);
    }
}
