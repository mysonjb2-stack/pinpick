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
    @if(!empty($photos) && count($photos) > 0)
    <div class="pp-cur-card__photos">
        @foreach($photos as $photo)
        <div class="pp-cur-card__photo" data-full="{{ asset('storage/' . $photo) }}" onclick="openLightbox(this)">
            <img src="{{ asset('storage/' . \App\Services\ImageProcessor::thumbPathFor($photo)) }}" alt="" loading="lazy">
        </div>
        @endforeach
    </div>
    @elseif(!empty($thumbnailUrl))
    <div class="pp-cur-card__photos">
        <div class="pp-cur-card__photo">
            <img src="{{ $thumbnailUrl }}" alt="" loading="lazy">
        </div>
    </div>
    @elseif($latitude && $longitude)
    <div class="pp-cur-card__photos">
        <div class="pp-cur-card__photo">
            <img src="/api/static-map?lat={{ $latitude }}&lng={{ $longitude }}&overseas={{ $isOverseas ? 1 : 0 }}&w=320&h=320" alt="{{ $placeName }} 위치 지도" loading="lazy">
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
        @if(!empty($isCourse) && !empty($dayNumber))
            <span class="pp-cur-card__day">Day {{ $dayNumber }}</span>
        @endif
        <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="pp-cur-card__map-chip" onclick="event.stopPropagation()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
            {{ $mapLabel }} ↗
        </a>
    </div>
</div>
