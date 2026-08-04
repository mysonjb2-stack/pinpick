{{--
  Shared place card for /c/{id} and /s/{token}
  Required variables:
    $cardIdx       - 0-based index
    $placeId       - place ID
    $placeName     - display name
    $categoryLabel - category label (nullable)
    $address       - road address (nullable)
    $latitude      - float (nullable)
    $longitude     - float (nullable)
    $isOverseas    - bool
    $mapUrl        - external map link
    $mapLabel      - '네이버 지도' or '구글 지도'
  Optional:
    $photos        - array of storage paths (curation only)
    $editorNote    - string (curation only)
    $sourceChannel - string (curation only)
    $sourceDate    - Carbon date (curation only)
    $sourceUrl     - string (curation only)
    $memo          - string (share only)
    $dayNumber     - int (course type only)
    $isCourse      - bool
    $detailLocation - string (nullable)
    $buildingName  - string (nullable)
    $googleReviewData - array|null (rating, review_count, featured, reviews, place_id)
--}}
<div class="pp-cur-card" data-idx="{{ $cardIdx }}" data-lat="{{ $latitude }}" data-lng="{{ $longitude }}" data-id="{{ $placeId }}" data-day="{{ $dayNumber ?? '' }}">
    <div class="pp-cur-card__top">
        <div class="pp-cur-card__info">
            <h3 class="pp-cur-card__name">
                <span class="pp-cur-card__num">{{ $cardIdx + 1 }}</span>
                {{ $placeName }}
            </h3>
            <div class="pp-cur-card__sub">
                @if($categoryLabel)<span class="pp-cur-card__cat">{{ $categoryLabel }}</span>@endif
                @if($address){{ $address }}@endif
            </div>
        </div>
        <button type="button" class="pp-cur-card__add" data-place-id="{{ $placeId }}" aria-label="담기">
            <svg class="pp-cur-card__plus" width="14" height="14" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="11" y1="5" x2="11" y2="17"/><line x1="5" y1="11" x2="17" y2="11"/></svg>
            <svg class="pp-cur-card__check" width="14" height="14" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 11 10 15 16 7"/></svg>
            <span class="pp-cur-card__add-label--off">담기</span>
            <span class="pp-cur-card__add-label--on">담음</span>
        </button>
    </div>
    @if(!empty($googleReviewData))
    <div class="pp-cur-card__grev" onclick="event.stopPropagation();var c=this.closest('.pp-cur-card'),b=c.querySelector('.pp-cur-card__grev-body');b.classList.toggle('is-open');this.classList.toggle('is-open')">
        <span class="pp-cur-card__grev-stars">@for($s = 1; $s <= 5; $s++)<svg width="12" height="12" viewBox="0 0 24 24" fill="{{ $s <= round($googleReviewData['rating']) ? '#FBBC04' : '#ddd' }}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>@endfor</span>
        <span class="pp-cur-card__grev-rating">{{ number_format($googleReviewData['rating'], 1) }}</span>
        <span class="pp-cur-card__grev-sep">·</span>
        <span class="pp-cur-card__grev-label">Google 리뷰 {{ number_format($googleReviewData['review_count']) }}개</span>
        <svg class="pp-cur-card__grev-arrow" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 6 15 12 9 18"/></svg>
    </div>
    @if(!empty($googleReviewData['featured']))
    @php $ft = $googleReviewData['featured']; @endphp
    <div class="pp-cur-card__grev-feat" onclick="event.stopPropagation();var c=this.closest('.pp-cur-card'),h=c.querySelector('.pp-cur-card__grev'),b=c.querySelector('.pp-cur-card__grev-body');b.classList.toggle('is-open');h.classList.toggle('is-open')">
        <svg class="pp-cur-card__grev-feat-icon" width="14" height="14" viewBox="0 0 48 48" fill="none"><path d="M44.5 20H24v8.5h11.8C34.7 33.9 30.1 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 11.8 2 2 11.8 2 24s9.8 22 22 22c11 0 21-8 21-22 0-1.3-.2-2.7-.5-4z" fill="#4285F4"/><path d="M3.2 14.1l7 5.2C12 15 17.5 11 24 11c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 14.9 2 7.2 6.8 3.2 14.1z" fill="#EA4335"/><path d="M24 46c5.5 0 10.4-1.8 14.3-5l-6.6-5.6C29.5 37.1 26.9 38 24 38c-6 0-11.1-4-12.8-9.5l-7 5.4C7.9 41.3 15.4 46 24 46z" fill="#34A853"/><path d="M44.5 20H24v8.5h11.8c-1 3-2.8 5.3-5.1 6.9l6.6 5.6c3.9-3.6 6.2-8.9 6.2-15 0-1.3-.2-2.7-.5-4z" fill="#FBBC05"/></svg>
        <p class="pp-cur-card__grev-feat-text">{{ $ft['text'] }}</p>
        <span class="pp-cur-card__grev-feat-meta">{{ $ft['author'] }} · @for($s = 1; $s <= 5; $s++)<svg width="9" height="9" viewBox="0 0 24 24" fill="{{ $s <= $ft['rating'] ? '#FBBC04' : '#ddd' }}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>@endfor</span>
    </div>
    @endif
    <div class="pp-cur-card__grev-body" onclick="event.stopPropagation()">
        @foreach($googleReviewData['reviews'] as $rev)
        <div class="pp-cur-card__grev-item">
            <div class="pp-cur-card__grev-head">
                <span class="pp-cur-card__grev-author">{{ $rev['author'] }} 님</span>
                <span class="pp-cur-card__grev-date">{{ $rev['time'] }}</span>
            </div>
            <div class="pp-cur-card__grev-score">@for($s = 1; $s <= 5; $s++)<svg width="10" height="10" viewBox="0 0 24 24" fill="{{ $s <= $rev['rating'] ? '#FBBC04' : '#ddd' }}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>@endfor <span>{{ $rev['rating'] }}</span></div>
            <p class="pp-cur-card__grev-text">{{ $rev['text'] }}</p>
        </div>
        @endforeach
        <a href="https://www.google.com/maps/place/?q=place_id:{{ $googleReviewData['place_id'] ?? '' }}" target="_blank" rel="noopener" class="pp-cur-card__grev-more">Google에서 더 보기 ↗</a>
    </div>
    @endif
    @if(!empty($photos) && count($photos) > 0)
    @php $fewClass = count($photos) <= 2 ? ' pp-cur-card__photos--few' : ''; @endphp
    <div class="pp-cur-card__photos{{ $fewClass }}">
        @foreach($photos as $photo)
        <div class="pp-cur-card__photo" data-full="{{ asset('storage/' . $photo) }}" onclick="openLightbox(this)">
            <img src="{{ asset('storage/' . \App\Services\ImageProcessor::thumbPathFor($photo)) }}" alt="" loading="lazy" width="110" height="110">
        </div>
        @endforeach
    </div>
    @elseif(!empty($thumbnailUrl))
    <div class="pp-cur-card__photos pp-cur-card__photos--few">
        <div class="pp-cur-card__photo">
            <img src="{{ $thumbnailUrl }}" alt="" loading="lazy" width="110" height="110">
        </div>
    </div>
    @elseif($latitude && $longitude)
    <div class="pp-cur-card__photos pp-cur-card__photos--few">
        <div class="pp-cur-card__photo">
            <img src="/api/static-map?lat={{ $latitude }}&lng={{ $longitude }}&overseas={{ $isOverseas ? 1 : 0 }}&w=320&h=320" alt="{{ $placeName }} 위치 지도" loading="lazy" width="110" height="110">
        </div>
    </div>
    @endif
    @if(!empty($editorNote))
        <p class="pp-cur-card__note">"{{ $editorNote }}"</p>
    @endif
    @if(!empty($memo))
        <p class="pp-cur-card__note">{{ $memo }}</p>
    @endif
    @if(!empty($sourceChannel))
        <p class="pp-cur-card__source">
            {{ $sourceChannel }}
            @if(!empty($sourceDate)) {{ $sourceDate->format('Y.m.d') }} 소개@endif
            @if(!empty($sourceUrl))
                · <a href="{{ $sourceUrl }}" target="_blank" rel="noopener" onclick="event.stopPropagation()">원본 보기 ↗</a>
            @endif
        </p>
    @endif
    <div class="pp-cur-card__actions">
        @if(!empty($isCourse) && !empty($dayNumber) && ($durationDays ?? 0) > 1)
            <span class="pp-cur-card__day">Day {{ $dayNumber }}</span>
        @endif
        <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="pp-cur-card__map-chip" onclick="event.stopPropagation()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
            {{ $mapLabel }} ↗
        </a>
    </div>
</div>
