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
                <label>구분</label>
                <div style="display:flex;gap:8px">
                    <label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer">
                        <input type="radio" name="persona_scope" value="topic" checked> 주제형
                    </label>
                    <label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer">
                        <input type="radio" name="persona_scope" value="region" onchange="document.getElementById('regionTagGroup').hidden = !this.checked"> 지역형
                    </label>
                </div>
            </div>
            <div class="ad-form-group" id="regionTagGroup" hidden>
                <label>지역 태그</label>
                <select class="ad-input" name="persona_region_tag">
                    <option value="">선택</option>
                    <option value="수도권">수도권</option>
                    <option value="부산·경남">부산·경남</option>
                    <option value="대구·경북">대구·경북</option>
                    <option value="광주·전라">광주·전라</option>
                    <option value="대전·충청">대전·충청</option>
                    <option value="강원">강원</option>
                    <option value="제주">제주</option>
                </select>
            </div>
            <div class="ad-form-group">
                <label>아바타 이미지</label>
                <input type="file" name="avatar" accept="image/*" class="ad-input" style="padding:6px">
                <small style="color:var(--ad-text-sub);font-size:11px">미업로드 시 DiceBear 일러스트 자동 생성</small>
            </div>
            <button type="submit" class="ad-btn ad-btn--primary" style="width:100%;margin-top:8px">생성</button>
        </form>

        <hr style="margin:20px 0;border:none;border-top:1px solid var(--ad-border,#E5E7EB)">

        {{-- 추천 채우기 --}}
        <h3 style="margin:0 0 12px;font-size:15px">추천 채우기</h3>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
            <span style="font-size:13px;color:var(--ad-text-sub)">목표 개수:</span>
            <form method="POST" action="{{ route('admin.personas.target-count') }}" style="display:flex;align-items:center;gap:6px">
                @csrf
                <input type="number" name="target_count" value="{{ $targetCount }}" min="1" max="50" class="ad-input" style="width:60px;text-align:center;padding:4px 8px">
                <button class="ad-btn ad-btn--sm">변경</button>
            </form>
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
            <span style="font-size:13px;color:var(--ad-text-sub)">주제:지역 비율:</span>
            <form method="POST" action="{{ route('admin.personas.topic-ratio') }}" style="display:flex;align-items:center;gap:6px">
                @csrf
                <input type="number" name="topic_ratio" value="{{ $topicRatio }}" min="0" max="100" step="10" class="ad-input" style="width:55px;text-align:center;padding:4px 8px">
                <span style="font-size:12px;color:var(--ad-text-sub)">%</span>
                <button class="ad-btn ad-btn--sm">변경</button>
            </form>
        </div>
        <div style="font-size:13px;color:var(--ad-text-sub);margin-bottom:12px">
            현재 {{ $personas->count() }}개 / 목표 {{ $targetCount }}개
            @if($personas->count() < $targetCount)
                <span style="color:#E65100"> — {{ $targetCount - $personas->count() }}개 부족</span>
            @else
                <span style="color:#2E7D32"> — 충분</span>
            @endif
            <br>후보 풀 잔여: {{ $poolRemaining }}개
            <span style="font-size:11px">(주제 {{ $poolRemainingByScope['topic'] }} / 지역 {{ $poolRemainingByScope['region'] }})</span>
        </div>
        <form method="POST" action="{{ route('admin.personas.seed') }}" style="margin-bottom:8px">
            @csrf
            <button class="ad-btn ad-btn--primary" style="width:100%" onclick="return confirm('후보 풀에서 부족분({{ max(0, $targetCount - $personas->count()) }}개)을 채웁니다.\n비율: 주제 {{ $topicRatio }}% / 지역 {{ 100 - $topicRatio }}%')">
                페르소나 추천 채우기
            </button>
        </form>
        @if($poolRemaining === 0)
        <div style="padding:10px;background:#FFF3E0;border-radius:8px;font-size:12px;color:#E65100;margin-bottom:8px">
            후보 풀이 소진되었습니다
        </div>
        @endif
        <button type="button" class="ad-btn" style="width:100%;color:#1565C0" onclick="openAiPanel()">AI로 생성</button>
    </div>

    {{-- 목록 --}}
    <div class="ad-card" style="flex:1;min-width:400px;padding:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
            <h3 style="margin:0;font-size:15px">페르소나 목록 ({{ $personas->count() }})</h3>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <form method="POST" action="{{ route('admin.personas.regenerate-all-avatars') }}" style="display:inline">
                    @csrf
                    <button class="ad-btn ad-btn--sm" style="color:#1565C0" onclick="return confirm('전체 {{ $personas->count() }}개 아바타를 재생성합니다.\n기존 아바타가 새 일러스트로 교체됩니다.')">전체 아바타 재생성</button>
                </form>
                <button class="ad-btn ad-btn--sm" onclick="document.getElementById('excludedSection').hidden = !document.getElementById('excludedSection').hidden">제외 이력 ({{ $excluded->count() }})</button>
            </div>
        </div>

        {{-- 제외 이력 섹션 --}}
        <div id="excludedSection" hidden style="margin-bottom:16px;padding:14px;background:#FAFAFA;border-radius:8px;border:1px solid var(--ad-border,#E5E7EB)">
            <h4 style="margin:0 0 10px;font-size:13px;font-weight:600">삭제된 닉네임 제외 이력</h4>
            <p style="font-size:11px;color:var(--ad-text-sub);margin:0 0 10px">이 목록의 닉네임은 추천 채우기 시 재사용되지 않습니다. 해제하면 다시 후보 풀에 포함됩니다.</p>
            @if($excluded->isEmpty())
                <p style="font-size:13px;color:var(--ad-text-sub);margin:0">제외 이력이 없습니다.</p>
            @else
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                @foreach($excluded as $ex)
                    <form method="POST" action="{{ route('admin.personas.restore-excluded') }}" style="display:inline" onsubmit="return confirm('{{ $ex->nickname }}을(를) 제외 이력에서 해제합니다.\n후보 풀에 다시 포함됩니다.')">
                        @csrf
                        <input type="hidden" name="id" value="{{ $ex->id }}">
                        <button class="ad-btn ad-btn--sm" style="font-size:12px" title="제외일: {{ $ex->excluded_at }}">
                            {{ $ex->nickname }} <span style="color:#C62828;margin-left:2px">&times;</span>
                        </button>
                    </form>
                @endforeach
                </div>
            @endif
        </div>

        @if($personas->isEmpty())
            <p style="color:var(--ad-text-sub);text-align:center;padding:30px 0">등록된 페르소나가 없습니다.</p>
        @else
            <table class="ad-table">
                <thead>
                    <tr><th style="width:50px"></th><th>닉네임</th><th>구분</th><th>소개</th><th>생성일</th><th></th></tr>
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
                        <td>
                            @if($p->persona_scope === 'region')
                                <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#E3F2FD;color:#1565C0">지역{{ $p->persona_region_tag ? '·'.$p->persona_region_tag : '' }}</span>
                            @else
                                <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#F3E5F5;color:#7B1FA2">주제</span>
                            @endif
                        </td>
                        <td style="color:var(--ad-text-sub);font-size:13px">{{ $p->bio ?: '-' }}</td>
                        <td style="font-size:12px;color:var(--ad-text-sub)">{{ $p->created_at->format('Y-m-d') }}</td>
                        <td style="white-space:nowrap">
                            <form method="POST" action="{{ route('admin.personas.regenerate-avatar', $p) }}" style="display:inline">
                                @csrf
                                <button class="ad-btn ad-btn--sm" style="color:#1565C0" title="아바타 재생성">🎨</button>
                            </form>
                            <button class="ad-btn ad-btn--sm" onclick="editPersona({{ $p->id }}, '{{ e($p->name) }}', '{{ e($p->bio) }}', '{{ $p->persona_scope ?? 'topic' }}', '{{ e($p->persona_region_tag ?? '') }}')">수정</button>
                            <form method="POST" action="{{ route('admin.personas.destroy', $p) }}" style="display:inline" onsubmit="return confirm('삭제하시겠습니까?\n닉네임이 제외 이력에 기록되어 추천 채우기에서 재사용되지 않습니다.')">
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
                <label>구분</label>
                <div style="display:flex;gap:8px">
                    <label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer">
                        <input type="radio" name="persona_scope" id="editScopeTopic" value="topic"> 주제형
                    </label>
                    <label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer">
                        <input type="radio" name="persona_scope" id="editScopeRegion" value="region"> 지역형
                    </label>
                </div>
            </div>
            <div class="ad-form-group" id="editRegionTagGroup" hidden>
                <label>지역 태그</label>
                <select class="ad-input" name="persona_region_tag" id="editRegionTag">
                    <option value="">선택</option>
                    <option value="수도권">수도권</option>
                    <option value="부산·경남">부산·경남</option>
                    <option value="대구·경북">대구·경북</option>
                    <option value="광주·전라">광주·전라</option>
                    <option value="대전·충청">대전·충청</option>
                    <option value="강원">강원</option>
                    <option value="제주">제주</option>
                </select>
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

{{-- AI 생성 모달 --}}
<div id="aiModal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.4);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:24px;width:90%;max-width:480px;max-height:85vh;display:flex;flex-direction:column">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <h3 style="margin:0;font-size:15px">AI 페르소나 생성</h3>
            <button type="button" style="border:none;background:none;font-size:18px;color:var(--ad-text-sub);cursor:pointer" onclick="closeAiPanel()">&times;</button>
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;flex-wrap:wrap">
            <label style="font-size:13px;white-space:nowrap">생성 개수:</label>
            <input type="number" id="aiCount" value="5" min="1" max="10" class="ad-input" style="width:60px;text-align:center;padding:4px 8px">
            <label style="font-size:13px;white-space:nowrap;margin-left:8px">유형:</label>
            <select id="aiScope" class="ad-input" style="width:auto;padding:4px 8px">
                <option value="mixed">혼합</option>
                <option value="topic">주제형만</option>
                <option value="region">지역형만</option>
            </select>
        </div>
        <div style="margin-bottom:12px">
            <button type="button" class="ad-btn ad-btn--primary" id="aiGenerateBtn" onclick="generateAi()" style="width:100%">생성 요청</button>
        </div>
        <div id="aiLoading" hidden style="text-align:center;padding:20px;color:var(--ad-text-sub);font-size:13px">
            Claude Haiku에게 요청 중...
        </div>
        <div id="aiError" hidden style="padding:10px;background:#FFEBEE;border-radius:8px;font-size:13px;color:#C62828;margin-bottom:12px"></div>
        <div id="aiResults" style="flex:1;overflow-y:auto"></div>
        <form method="POST" action="{{ route('admin.personas.store-ai') }}" id="aiForm" style="display:none">
            @csrf
            <div id="aiHiddenInputs"></div>
            <div style="display:flex;gap:8px;margin-top:12px">
                <button type="submit" class="ad-btn ad-btn--primary" style="flex:1" id="aiSaveBtn">선택한 항목 저장</button>
                <button type="button" class="ad-btn" style="flex:1" onclick="closeAiPanel()">취소</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPersona(id, name, bio, scope, regionTag) {
    document.getElementById('editForm').action = '/admin/personas/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editBio').value = bio;
    if (scope === 'region') {
        document.getElementById('editScopeRegion').checked = true;
        document.getElementById('editRegionTagGroup').hidden = false;
        document.getElementById('editRegionTag').value = regionTag || '';
    } else {
        document.getElementById('editScopeTopic').checked = true;
        document.getElementById('editRegionTagGroup').hidden = true;
    }
    document.getElementById('editModal').style.display = 'flex';
}
document.getElementById('editScopeRegion').addEventListener('change', function() {
    document.getElementById('editRegionTagGroup').hidden = !this.checked;
});
document.getElementById('editScopeTopic').addEventListener('change', function() {
    document.getElementById('editRegionTagGroup').hidden = this.checked;
});
// 새 페르소나 폼 지역형 라디오
document.querySelectorAll('input[name="persona_scope"]').forEach(function(r) {
    if (r.closest('#editForm')) return;
    r.addEventListener('change', function() {
        document.getElementById('regionTagGroup').hidden = this.value !== 'region';
    });
});
function closeEdit() {
    document.getElementById('editModal').style.display = 'none';
}

function openAiPanel() {
    document.getElementById('aiModal').style.display = 'flex';
    document.getElementById('aiResults').innerHTML = '';
    document.getElementById('aiForm').style.display = 'none';
    document.getElementById('aiError').hidden = true;
}
function closeAiPanel() {
    document.getElementById('aiModal').style.display = 'none';
}

function generateAi() {
    var count = document.getElementById('aiCount').value;
    var scope = document.getElementById('aiScope').value;
    var btn = document.getElementById('aiGenerateBtn');
    var loading = document.getElementById('aiLoading');
    var errorEl = document.getElementById('aiError');
    var results = document.getElementById('aiResults');

    btn.disabled = true;
    btn.textContent = '요청 중...';
    loading.hidden = false;
    errorEl.hidden = true;
    results.innerHTML = '';
    document.getElementById('aiForm').style.display = 'none';

    fetch('{{ route("admin.personas.generate-ai") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ count: parseInt(count), scope: scope })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        loading.hidden = true;
        btn.disabled = false;
        btn.textContent = '생성 요청';

        if (data.error) {
            errorEl.textContent = data.error;
            errorEl.hidden = false;
            return;
        }

        if (!data.candidates || !data.candidates.length) {
            errorEl.textContent = '생성된 후보가 없습니다. 다시 시도하세요.';
            errorEl.hidden = false;
            return;
        }

        renderAiCandidates(data.candidates);
    })
    .catch(function(e) {
        loading.hidden = true;
        btn.disabled = false;
        btn.textContent = '생성 요청';
        errorEl.textContent = '네트워크 오류: ' + e.message;
        errorEl.hidden = false;
    });
}

function renderAiCandidates(candidates) {
    var results = document.getElementById('aiResults');
    var html = '<div style="font-size:12px;color:var(--ad-text-sub);margin-bottom:10px">저장할 항목을 선택하세요</div>';
    candidates.forEach(function(c, i) {
        var badge = c.scope === 'region'
            ? '<span style="display:inline-block;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:600;background:#E3F2FD;color:#1565C0;margin-left:6px">지역' + (c.region_tag ? '·' + esc(c.region_tag) : '') + '</span>'
            : '<span style="display:inline-block;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:600;background:#F3E5F5;color:#7B1FA2;margin-left:6px">주제</span>';
        html += '<label style="display:flex;align-items:center;gap:10px;padding:10px;border:1px solid var(--ad-border,#E5E7EB);border-radius:8px;margin-bottom:6px;cursor:pointer">'
            + '<input type="checkbox" class="ai-check" data-idx="' + i + '" data-name="' + esc(c.name) + '" data-bio="' + esc(c.bio) + '" data-scope="' + esc(c.scope || 'topic') + '" data-region-tag="' + esc(c.region_tag || '') + '" checked>'
            + '<div style="flex:1"><strong style="font-size:14px">' + esc(c.name) + '</strong>' + badge + '<div style="font-size:12px;color:var(--ad-text-sub);margin-top:2px">' + esc(c.bio) + '</div></div>'
            + '</label>';
    });
    results.innerHTML = html;
    document.getElementById('aiForm').style.display = '';
    updateAiForm();
    results.querySelectorAll('.ai-check').forEach(function(cb) {
        cb.addEventListener('change', updateAiForm);
    });
}

function updateAiForm() {
    var inputs = document.getElementById('aiHiddenInputs');
    inputs.innerHTML = '';
    var checked = document.querySelectorAll('.ai-check:checked');
    var idx = 0;
    checked.forEach(function(cb) {
        inputs.innerHTML += '<input type="hidden" name="items[' + idx + '][name]" value="' + cb.dataset.name + '">'
            + '<input type="hidden" name="items[' + idx + '][bio]" value="' + cb.dataset.bio + '">'
            + '<input type="hidden" name="items[' + idx + '][scope]" value="' + cb.dataset.scope + '">'
            + '<input type="hidden" name="items[' + idx + '][region_tag]" value="' + cb.dataset.regionTag + '">';
        idx++;
    });
    document.getElementById('aiSaveBtn').textContent = '선택한 ' + idx + '개 저장';
    document.getElementById('aiSaveBtn').disabled = idx === 0;
}

function esc(s) {
    var d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}
</script>
@endsection
