@extends('admin.layouts.app')
@section('title', '페르소나 계정')

@section('content')
<div style="display:flex;gap:20px;flex-wrap:wrap">
    {{-- 생성 폼 --}}
    <div class="ad-card" style="flex:0 0 320px;padding:20px;align-self:flex-start">
        <h3 style="margin:0 0 16px;font-size:15px">새 페르소나</h3>
        <form method="POST" action="{{ route('admin.personas.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="ad-form-group">
                <label>닉네임 (2~8자) *</label>
                <input class="ad-input" name="name" required minlength="2" maxlength="8" placeholder="예: 분당맛집러">
            </div>
            <div class="ad-form-group">
                <label>소개 한 줄</label>
                <input class="ad-input" name="bio" maxlength="100" placeholder="예: 분당 10년차 맛집 탐방가">
            </div>
            <div class="ad-form-group">
                <label>아바타 이미지</label>
                <input type="file" name="avatar" accept="image/*" class="ad-input" style="padding:6px">
                <small style="color:var(--ad-text-sub);font-size:11px">미업로드 시 DiceBear 일러스트 자동 생성</small>
            </div>
            <button type="submit" class="ad-btn ad-btn--primary" style="width:100%;margin-top:8px">생성</button>
        </form>
    </div>

    {{-- 목록 --}}
    <div class="ad-card" style="flex:1;min-width:400px;padding:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
            <h3 style="margin:0;font-size:15px">페르소나 목록 ({{ $personas->count() }})</h3>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <form method="POST" action="{{ route('admin.personas.regenerate-all-avatars') }}" style="display:inline">
                    @csrf
                    <button class="ad-btn ad-btn--sm" style="color:#1565C0" onclick="return confirm('전체 {{ $personas->count() }}개 아바타를 재생성합니다.\n기존 아바타가 새 일러스트로 교체됩니다.')">🎨 전체 아바타 재생성</button>
                </form>
                <form method="POST" action="{{ route('admin.personas.seed') }}" style="display:inline">
                    @csrf
                    <button class="ad-btn ad-btn--sm" onclick="return confirm('기본 페르소나 12개를 생성합니다.')">기본 페르소나 12개 생성</button>
                </form>
            </div>
        </div>
        @if($personas->isEmpty())
            <p style="color:var(--ad-text-sub);text-align:center;padding:30px 0">등록된 페르소나가 없습니다.</p>
        @else
            <table class="ad-table">
                <thead>
                    <tr><th style="width:50px"></th><th>닉네임</th><th>소개</th><th>생성일</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($personas as $p)
                    <tr>
                        <td>
                            @if($p->profile_image)
                                <img src="{{ $p->profile_image }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover">
                            @else
                                @php $hue = crc32($p->name) % 360; @endphp
                                <span style="display:inline-flex;width:36px;height:36px;border-radius:50%;background:hsl({{ $hue }},45%,55%);align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff">{{ mb_substr($p->name, 0, 1) }}</span>
                            @endif
                        </td>
                        <td><strong>{{ $p->name }}</strong></td>
                        <td style="color:var(--ad-text-sub);font-size:13px">{{ $p->bio ?: '-' }}</td>
                        <td style="font-size:12px;color:var(--ad-text-sub)">{{ $p->created_at->format('Y-m-d') }}</td>
                        <td style="white-space:nowrap">
                            <form method="POST" action="{{ route('admin.personas.regenerate-avatar', $p) }}" style="display:inline">
                                @csrf
                                <button class="ad-btn ad-btn--sm" style="color:#1565C0" title="아바타 재생성">🎨</button>
                            </form>
                            <button class="ad-btn ad-btn--sm" onclick="editPersona({{ $p->id }}, '{{ e($p->name) }}', '{{ e($p->bio) }}')">수정</button>
                            <form method="POST" action="{{ route('admin.personas.destroy', $p) }}" style="display:inline" onsubmit="return confirm('삭제하시겠습니까?')">
                                @csrf @method('DELETE')
                                <button class="ad-btn ad-btn--sm" style="color:#C62828">삭제</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
        <p style="margin:12px 0 0;font-size:11px;color:var(--ad-text-sub)">
            아바타: <a href="https://www.dicebear.com" target="_blank" rel="noopener">DiceBear</a> —
            Adventurer by Lisa Wischofsky, Fun Emoji by Davis Uche (CC BY 4.0)
        </p>
    </div>
</div>

{{-- 수정 모달 --}}
<div id="editModal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.4);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:24px;width:90%;max-width:360px">
        <h3 style="margin:0 0 16px;font-size:15px">페르소나 수정</h3>
        <form method="POST" id="editForm" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="ad-form-group">
                <label>닉네임 *</label>
                <input class="ad-input" name="name" id="editName" required minlength="2" maxlength="8">
            </div>
            <div class="ad-form-group">
                <label>소개 한 줄</label>
                <input class="ad-input" name="bio" id="editBio" maxlength="100">
            </div>
            <div class="ad-form-group">
                <label>아바타 변경 (수동 업로드)</label>
                <input type="file" name="avatar" accept="image/*" class="ad-input" style="padding:6px">
                <small style="color:var(--ad-text-sub);font-size:11px">수동 업로드 시 자동 생성 아바타를 대체합니다</small>
            </div>
            <div style="display:flex;gap:8px;margin-top:12px">
                <button type="submit" class="ad-btn ad-btn--primary" style="flex:1">저장</button>
                <button type="button" class="ad-btn" style="flex:1" onclick="closeEdit()">취소</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPersona(id, name, bio) {
    document.getElementById('editForm').action = '/admin/personas/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editBio').value = bio;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEdit() {
    document.getElementById('editModal').style.display = 'none';
}
</script>
@endsection
