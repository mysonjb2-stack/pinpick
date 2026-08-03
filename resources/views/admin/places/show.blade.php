@extends('admin.layouts.app')
@section('title', $place->name . ' — 장소 상세')

@section('content')
<div style="margin-bottom:16px">
    <a href="{{ route('admin.places.index') }}" class="ad-btn ad-btn--sm">&larr; 장소목록</a>
</div>

<div class="ad-grid-2" style="margin-bottom:24px">
    <div class="ad-card">
        <div class="ad-card__title" style="margin-bottom:12px">장소 정보</div>
        <table class="ad-table">
            <tr><th style="width:100px">ID</th><td>{{ $place->id }}</td></tr>
            <tr><th>장소명</th><td>{{ $place->name }}</td></tr>
            @if($place->original_name && $place->original_name !== $place->name)
            <tr><th>원본명</th><td class="ad-text-sub">{{ $place->original_name }}</td></tr>
            @endif
            <tr><th>카테고리</th><td>{{ $place->category?->name ?? '-' }}</td></tr>
            <tr>
                <th>테마</th>
                <td>
                    @forelse($place->themes as $theme)
                        <span class="ad-badge ad-badge--blue">{{ $theme->name }}</span>
                    @empty
                        <span class="ad-text-sub">-</span>
                    @endforelse
                </td>
            </tr>
            <tr><th>주소</th><td>{{ $place->road_address ?: $place->address }}</td></tr>
            <tr><th>전화</th><td>{{ $place->phone ?: '-' }}</td></tr>
            <tr>
                <th>구분</th>
                <td>
                    <span class="ad-badge {{ $place->is_overseas ? 'ad-badge--blue' : 'ad-badge--green' }}">{{ $place->is_overseas ? '해외' : '국내' }}</span>
                    <span class="ad-badge {{ $place->status === 'visited' ? 'ad-badge--green' : 'ad-badge--amber' }}">{{ $place->status === 'visited' ? '방문완료' : '방문예정' }}</span>
                </td>
            </tr>
            <tr><th>좌표</th><td class="ad-text-sub">{{ $place->lat }}, {{ $place->lng }}</td></tr>
            <tr>
                <th>지역코드</th>
                <td>
                    @if($place->country_code)
                        <span class="ad-badge ad-badge--blue">{{ $place->country_code }}</span>
                        {{ $place->region_l1 }} {{ $place->region_l2 }}
                        @if($place->region_l1_key)
                            <span class="ad-text-sub" style="font-size:11px">(key: {{ $place->region_l1_key }}{{ $place->region_l2_key ? '/'.$place->region_l2_key : '' }})</span>
                        @endif
                    @else
                        <span class="ad-text-sub">미설정</span>
                    @endif
                </td>
            </tr>
            <tr><th>등록일</th><td>{{ $place->created_at->format('Y-m-d H:i') }}</td></tr>
            <tr>
                <th>등록자</th>
                <td>
                    @if($place->user)
                        <a href="{{ route('admin.users.show', $place->user) }}">{{ $place->user->name }}</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
        </table>
    </div>
    <div class="ad-card">
        <div class="ad-card__title" style="margin-bottom:12px">이미지 ({{ $place->images->count() }})</div>
        @if($place->images->count())
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @foreach($place->images as $img)
                    <img src="{{ $img->url }}" alt="" style="width:100px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--ad-border)">
                @endforeach
            </div>
        @else
            <div class="ad-text-sub">등록된 이미지 없음</div>
        @endif
        @if($place->memo)
            <div class="ad-card__title" style="margin:16px 0 8px">메모</div>
            <div style="padding:12px;background:var(--ad-bg);border-radius:8px;font-size:13px">{{ $place->memo }}</div>
        @endif
        <div style="margin-top:24px">
            <form method="POST" action="{{ route('admin.places.destroy', $place) }}" onsubmit="return confirm('정말 이 장소를 삭제하시겠습니까?')">
                @csrf @method('DELETE')
                <button type="submit" class="ad-btn ad-btn--danger">장소 삭제</button>
            </form>
        </div>
    </div>
</div>

@if($place->is_overseas && !$place->region_l1_key)
<div class="ad-card" style="margin-bottom:24px">
    <div class="ad-card__title" style="margin-bottom:12px">지역 수동 보정</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
        <input id="rgCC" type="text" class="ad-input" style="width:60px" placeholder="CC" value="{{ $place->country_code }}">
        <input id="rgL1" type="text" class="ad-input" style="width:160px" placeholder="region_l1 (표시)" value="{{ $place->region_l1 }}">
        <input id="rgL2" type="text" class="ad-input" style="width:160px" placeholder="region_l2 (표시)" value="{{ $place->region_l2 }}">
        <input id="rgK1" type="text" class="ad-input" style="width:120px" placeholder="l1_key (자동)" value="{{ $place->region_l1_key }}">
        <input id="rgK2" type="text" class="ad-input" style="width:120px" placeholder="l2_key (자동)" value="{{ $place->region_l2_key }}">
    </div>
    <div id="rgSimilar" style="margin-bottom:12px;display:none">
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
            <input type="checkbox" id="rgApplySimilar" checked>
            동일 장소에도 함께 적용 (<span id="rgSimilarCount">0</span>건)
        </label>
        <div id="rgSimilarList" style="font-size:12px;color:var(--ad-text-sub);margin-top:4px;padding-left:20px"></div>
    </div>
    <button class="ad-btn ad-btn--sm" onclick="saveRegionFix()">저장</button>
    <span id="rgMsg" style="font-size:12px;margin-left:8px"></span>
</div>
<script>
(function(){
    fetch('{{ route("admin.places.similar", $place) }}')
        .then(r=>r.json())
        .then(d=>{
            if(d.similar&&d.similar.length>0){
                document.getElementById('rgSimilar').style.display='block';
                document.getElementById('rgSimilarCount').textContent=d.similar.length;
                document.getElementById('rgSimilarList').innerHTML=d.similar.map(s=>
                    '#'+s.id+' '+s.name+' ('+s.user_name+')'
                ).join('<br>');
            }
        });
})();
function saveRegionFix(){
    const body={
        country_code:document.getElementById('rgCC').value,
        region_l1:document.getElementById('rgL1').value,
        region_l2:document.getElementById('rgL2').value,
        region_l1_key:document.getElementById('rgK1').value||undefined,
        region_l2_key:document.getElementById('rgK2').value||undefined,
        apply_similar:document.getElementById('rgApplySimilar')?.checked||false,
    };
    fetch('{{ route("admin.places.update-region", $place) }}',{
        method:'PUT',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body:JSON.stringify(body),
    }).then(r=>r.json()).then(d=>{
        if(d.success){
            const msg=document.getElementById('rgMsg');
            msg.textContent='저장 완료 ('+d.updated_ids.length+'건)';
            msg.style.color='green';
            setTimeout(()=>location.reload(),1000);
        }
    });
}
</script>
@endif
@endsection