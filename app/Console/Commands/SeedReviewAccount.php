<?php

namespace App\Console\Commands;

use App\Http\Controllers\CategoryController;
use App\Models\Category;
use App\Models\Place;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedReviewAccount extends Command
{
    protected $signature = 'review:seed {--reset : 기존 데이터 초기화 후 재생성}';
    protected $description = 'App Store 심사용 계정 및 샘플 데이터 생성';

    public function handle(): int
    {
        $email = config('services.review.email');
        $password = config('services.review.password');

        if (!$email || !$password) {
            $this->error('REVIEW_EMAIL / REVIEW_PASSWORD 가 .env에 설정되지 않았습니다.');
            return 1;
        }

        $user = User::where('email', $email)->first();

        if ($user && $this->option('reset')) {
            Place::where('user_id', $user->id)->forceDelete();
            Category::where('user_id', $user->id)->delete();
            $this->info('기존 데이터 초기화 완료');
        }

        if (!$user) {
            $user = User::create([
                'name' => '핀픽리뷰어',
                'email' => $email,
                'password' => Hash::make($password),
                'is_review_account' => true,
                'provider' => 'review',
                'provider_id' => 'appstore-review',
            ]);
            $this->info("심사 계정 생성: {$email}");
        } else {
            $user->update([
                'is_review_account' => true,
                'password' => Hash::make($password),
            ]);
            $this->info("기존 계정 업데이트: {$email}");
        }

        CategoryController::ensureUserCategories($user);

        $categories = Category::where('user_id', $user->id)->get();
        $catMap = [];
        foreach ($categories as $c) {
            $catMap[$c->name] = $c->id;
        }

        if (Place::where('user_id', $user->id)->count() > 0) {
            $this->info('장소 데이터가 이미 존재합니다. --reset 옵션으로 초기화 후 재실행하세요.');
            return 0;
        }

        $places = $this->getPlaceData();
        $created = 0;

        foreach ($places as $p) {
            $categoryId = $catMap[$p['category']] ?? ($catMap['자주 가는 곳'] ?? null);

            Place::create([
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'name' => $p['name'],
                'address' => $p['address'],
                'road_address' => $p['road_address'] ?? $p['address'],
                'lat' => $p['lat'],
                'lng' => $p['lng'],
                'memo' => $p['memo'] ?? null,
                'status' => 'visited',
                'is_overseas' => $p['is_overseas'] ?? false,
                'country_code' => $p['country_code'] ?? 'KR',
                'region_l1' => $p['region_l1'] ?? null,
                'region_l2' => $p['region_l2'] ?? null,
                'sort_order' => $created,
            ]);
            $created++;
        }

        $this->info("{$created}개 장소 생성 완료");
        $this->info('카테고리: ' . $categories->pluck('name')->join(', '));
        $this->info('심사 계정 준비 완료!');

        return 0;
    }

    private function getPlaceData(): array
    {
        return [
            // ── 맛집 (5곳) ──
            [
                'category' => '맛집',
                'name' => '을지로 노가리 골목',
                'address' => '서울 중구 을지로3가',
                'road_address' => '서울 중구 을지로 120',
                'lat' => 37.5660, 'lng' => 126.9921,
                'memo' => '퇴근 후 한 잔 하기 좋은 곳',
                'region_l1' => '서울', 'region_l2' => '중구',
            ],
            [
                'category' => '맛집',
                'name' => '광장시장 빈대떡',
                'address' => '서울 종로구 예지동',
                'road_address' => '서울 종로구 창경궁로 88',
                'lat' => 37.5700, 'lng' => 126.9996,
                'memo' => '녹두전이 진짜 맛있음',
                'region_l1' => '서울', 'region_l2' => '종로구',
            ],
            [
                'category' => '맛집',
                'name' => '해운대 부산곰장어',
                'address' => '부산 해운대구 중동',
                'road_address' => '부산 해운대구 구남로 24',
                'lat' => 35.1586, 'lng' => 129.1604,
                'memo' => '부산 가면 꼭 들르는 곳',
                'region_l1' => '부산', 'region_l2' => '해운대구',
            ],
            [
                'category' => '맛집',
                'name' => '전주 한옥마을 콩나물국밥',
                'address' => '전북 전주시 완산구 교동',
                'road_address' => '전북 전주시 완산구 전주천동로 68',
                'lat' => 35.8148, 'lng' => 127.1527,
                'region_l1' => '전북', 'region_l2' => '전주시',
            ],
            [
                'category' => '맛집',
                'name' => '이치란 라멘 시부야',
                'address' => '東京都渋谷区神南1-22-7',
                'road_address' => 'Shibuya, Tokyo',
                'lat' => 35.6612, 'lng' => 139.6994,
                'memo' => '도쿄 여행 때 꼭 가는 라멘집',
                'is_overseas' => true, 'country_code' => 'JP',
            ],

            // ── 카페 (5곳) ──
            [
                'category' => '카페',
                'name' => '카페 온다',
                'address' => '서울 성동구 성수동',
                'road_address' => '서울 성동구 서울숲2길 32',
                'lat' => 37.5446, 'lng' => 127.0410,
                'memo' => '루프탑에서 서울숲 뷰',
                'region_l1' => '서울', 'region_l2' => '성동구',
            ],
            [
                'category' => '카페',
                'name' => '테라로사 강릉본점',
                'address' => '강원 강릉시 구정면',
                'road_address' => '강원 강릉시 구정면 현천로 7',
                'lat' => 37.7810, 'lng' => 128.8556,
                'memo' => '강릉 드라이브 필수 코스',
                'region_l1' => '강원', 'region_l2' => '강릉시',
            ],
            [
                'category' => '카페',
                'name' => '블루보틀 삼청',
                'address' => '서울 종로구 삼청동',
                'road_address' => '서울 종로구 삼청로 76',
                'lat' => 37.5828, 'lng' => 126.9818,
                'region_l1' => '서울', 'region_l2' => '종로구',
            ],
            [
                'category' => '카페',
                'name' => 'Starbucks Reserve Roastery',
                'address' => '1124 Pike St, Seattle',
                'road_address' => '1124 Pike St, Seattle, WA',
                'lat' => 47.6141, 'lng' => -122.3278,
                'memo' => '시애틀 1호점, 분위기 최고',
                'is_overseas' => true, 'country_code' => 'US',
            ],
            [
                'category' => '카페',
                'name' => '아라비카 교토 히가시야마',
                'address' => '京都府京都市東山区星野町87-5',
                'road_address' => 'Higashiyama, Kyoto',
                'lat' => 34.9984, 'lng' => 135.7818,
                'memo' => '교토 감성 카페',
                'is_overseas' => true, 'country_code' => 'JP',
            ],

            // ── 여행 (5곳) ──
            [
                'category' => '여행',
                'name' => '남산타워',
                'address' => '서울 용산구 용산동2가',
                'road_address' => '서울 용산구 남산공원길 105',
                'lat' => 37.5512, 'lng' => 126.9882,
                'memo' => '야경 명소',
                'region_l1' => '서울', 'region_l2' => '용산구',
            ],
            [
                'category' => '여행',
                'name' => '감천문화마을',
                'address' => '부산 사하구 감내2로',
                'road_address' => '부산 사하구 감내2로 203',
                'lat' => 35.0975, 'lng' => 129.0106,
                'memo' => '부산 필수 관광지',
                'region_l1' => '부산', 'region_l2' => '사하구',
            ],
            [
                'category' => '여행',
                'name' => '제주 성산일출봉',
                'address' => '제주 서귀포시 성산읍',
                'road_address' => '제주 서귀포시 성산읍 일출로 284-12',
                'lat' => 33.4580, 'lng' => 126.9425,
                'region_l1' => '제주', 'region_l2' => '서귀포시',
            ],
            [
                'category' => '여행',
                'name' => '후시미이나리 신사',
                'address' => '京都府京都市伏見区深草薮之内町68',
                'road_address' => 'Fushimi, Kyoto',
                'lat' => 34.9671, 'lng' => 135.7727,
                'memo' => '천 개의 도리이, 꼭 아침 일찍',
                'is_overseas' => true, 'country_code' => 'JP',
            ],
            [
                'category' => '여행',
                'name' => '센트럴파크',
                'address' => 'New York, NY 10024',
                'road_address' => 'Central Park, New York',
                'lat' => 40.7829, 'lng' => -73.9654,
                'memo' => '뉴욕 산책 최고',
                'is_overseas' => true, 'country_code' => 'US',
            ],

            // ── 자주 가는 곳 (3곳) ──
            [
                'category' => '자주 가는 곳',
                'name' => '올리브영 강남역점',
                'address' => '서울 강남구 역삼동',
                'road_address' => '서울 강남구 강남대로 396',
                'lat' => 37.4979, 'lng' => 127.0276,
                'region_l1' => '서울', 'region_l2' => '강남구',
            ],
            [
                'category' => '자주 가는 곳',
                'name' => '코스트코 양재점',
                'address' => '서울 서초구 양재동',
                'road_address' => '서울 서초구 양재대로2길 18',
                'lat' => 37.4670, 'lng' => 127.0378,
                'region_l1' => '서울', 'region_l2' => '서초구',
            ],
            [
                'category' => '자주 가는 곳',
                'name' => '스타필드 코엑스몰',
                'address' => '서울 강남구 삼성동',
                'road_address' => '서울 강남구 영동대로 513',
                'lat' => 37.5118, 'lng' => 127.0590,
                'region_l1' => '서울', 'region_l2' => '강남구',
            ],
        ];
    }
}
