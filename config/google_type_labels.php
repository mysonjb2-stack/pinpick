<?php

// 해외 장소에서 실제 사용 중인 구글 Place 타입 → 한글 매핑
// curation_places.category_label 기준 전수 집계 (2026-08-04)
return [
    // 음식
    'restaurant' => '음식점',
    'vietnamese_restaurant' => '베트남 음식',
    'seafood_restaurant' => '해산물',
    'japanese_restaurant' => '일식',
    'korean_restaurant' => '한식',
    'chinese_restaurant' => '중식',
    'thai_restaurant' => '태국 음식',
    'indian_restaurant' => '인도 음식',
    'italian_restaurant' => '이탈리안',
    'french_restaurant' => '프렌치',
    'mexican_restaurant' => '멕시칸',
    'american_restaurant' => '미국 음식',
    'barbecue_restaurant' => '바베큐',
    'ramen_restaurant' => '라멘',
    'sushi_restaurant' => '초밥',
    'brunch_restaurant' => '브런치',
    'steak_house' => '스테이크',
    'hamburger_restaurant' => '햄버거',
    'pizza_restaurant' => '피자',
    'sandwich_shop' => '샌드위치',
    'noodle_restaurant' => '면요리',
    'fast_food_restaurant' => '패스트푸드',
    'vegan_restaurant' => '비건',
    'vegetarian_restaurant' => '채식',

    // 카페·디저트
    'cafe' => '카페',
    'coffee_shop' => '카페',
    'bakery' => '베이커리',
    'ice_cream_shop' => '아이스크림',
    'dessert_shop' => '디저트',
    'tea_house' => '찻집',

    // 관광·명소
    'tourist_attraction' => '관광명소',
    'scenic_spot' => '경관명소',
    'historical_landmark' => '역사유적',
    'castle' => '성·유적',
    'place_of_worship' => '사원·성당',
    'church' => '교회',
    'temple' => '사찰',
    'mosque' => '모스크',
    'shrine' => '신사',

    // 자연
    'park' => '공원',
    'garden' => '정원',
    'beach' => '해변',
    'national_park' => '국립공원',
    'hiking_area' => '등산',
    'marina' => '마리나',

    // 문화·엔터
    'museum' => '박물관',
    'art_gallery' => '미술관',
    'aquarium' => '수족관',
    'zoo' => '동물원',
    'amusement_park' => '놀이공원',
    'amusement_center' => '체험시설',
    'stadium' => '경기장',
    'performing_arts_theater' => '극장',
    'movie_theater' => '영화관',
    'night_club' => '클럽',
    'bar' => '바',

    // 쇼핑
    'shopping_mall' => '쇼핑몰',
    'market' => '시장',
    'supermarket' => '마트',
    'department_store' => '백화점',
    'clothing_store' => '의류',
    'book_store' => '서점',
    'gift_shop' => '기념품',
    'convenience_store' => '편의점',

    // 숙소
    'hotel' => '호텔',
    'resort_hotel' => '리조트',
    'lodging' => '숙소',
    'guest_house' => '게스트하우스',

    // 기타
    'spa' => '스파',
    'gym' => '피트니스',
    'hospital' => '병원',
    'pharmacy' => '약국',
    'airport' => '공항',
    'transit_station' => '역',
    'bus_station' => '버스터미널',

    // 범용 (우선순위 최하 — 다른 타입이 없을 때만 사용)
    'point_of_interest' => null,
    'establishment' => null,
    'food' => null,
    'store' => null,
    'health' => null,
    'general_contractor' => null,
    'political' => null,
    'locality' => null,
    'sublocality' => null,
];
