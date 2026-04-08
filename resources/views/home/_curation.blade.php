{{-- 큐레이션 섹션 (MVP 더미 데이터) --}}
<section class="pp-section">
    <div class="pp-section__head">
        <div class="pp-section__title">🔥 이번주 많이 저장된 장소</div>
        <a href="#" class="pp-section__more">더보기</a>
    </div>
    <div class="pp-hscroll">
        @foreach($curation['weekly'] as $i => $p)
            <div class="pp-hcard">
                <div class="pp-hcard__thumb">
                    <span class="pp-hcard__rank">#{{ $i + 1 }}</span>
                    {{ $p['icon'] }}
                </div>
                <div class="pp-hcard__body">
                    <div class="pp-hcard__name">{{ $p['name'] }}</div>
                    <div class="pp-hcard__meta">
                        <span>{{ $p['category'] }} · {{ $p['area'] }}</span>
                    </div>
                    <div class="pp-hcard__meta">
                        <span class="pp-hcard__saves">📌 {{ number_format($p['saves']) }} 저장</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="pp-section">
    <div class="pp-section__head">
        <div class="pp-section__title">✈️ 여행자들의 서울 핀픽</div>
        <a href="#" class="pp-section__more">더보기</a>
    </div>
    <div class="pp-hscroll">
        @foreach($curation['seoul_trip'] as $p)
            <div class="pp-hcard">
                <div class="pp-hcard__thumb">{{ $p['icon'] }}</div>
                <div class="pp-hcard__body">
                    <div class="pp-hcard__name">{{ $p['name'] }}</div>
                    <div class="pp-hcard__meta">{{ $p['category'] }} · {{ $p['area'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="pp-section" style="padding-bottom: 30px;">
    <div class="pp-section__head">
        <div class="pp-section__title">🍽 요즘 뜨는 맛집 핀</div>
        <a href="#" class="pp-section__more">더보기</a>
    </div>
    <div class="pp-hscroll">
        @foreach($curation['trending_food'] as $p)
            <div class="pp-hcard">
                <div class="pp-hcard__thumb">{{ $p['icon'] }}</div>
                <div class="pp-hcard__body">
                    <div class="pp-hcard__name">{{ $p['name'] }}</div>
                    <div class="pp-hcard__meta">{{ $p['category'] }} · {{ $p['area'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
</section>
