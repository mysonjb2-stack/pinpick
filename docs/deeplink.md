# 핀픽 딥링크 규격

## 커스텀 URL 스킴

```
pinpick://{path}[?query]
```

## 앱 동작 규칙 (범용)

`pinpick://{path}?{query}` 수신 시 → 앱 내 웹뷰를 `https://mypinpick.net/{path}?{query}` 로 로드

**쿼리 파라미터도 반드시 그대로 전달해야 합니다.**

| 딥링크 | 웹뷰 로드 URL | 설명 |
|--------|---------------|------|
| `pinpick://s/AbCd1234` | `https://mypinpick.net/s/AbCd1234` | 공유 페이지 |
| `pinpick://s/AbCd1234?selected=1,3&action=save` | `https://mypinpick.net/s/AbCd1234?selected=1,3&action=save` | 공유 페이지 + 1,3번 선택 + 저장 자동 시작 |
| `pinpick://` | `https://mypinpick.net/` | 홈 (저장 완료 후 앱 전환 시 사용) |
| `pinpick://places/123` | `https://mypinpick.net/places/123` | 장소 상세 |

> **범용 매핑**: `pinpick://{path}?{query}` → `https://mypinpick.net/{path}?{query}`
> 개별 path를 하드코딩하지 말고 이 규칙으로 처리하면 웹에서 경로가 추가되어도 앱 수정 없이 동작합니다.

## 공유 페이지 쿼리 파라미터

| 파라미터 | 값 | 설명 |
|----------|-----|------|
| `selected` | 콤마 구분 숫자 (예: `1,3,5`) | sort_order(핀 번호) 기준으로 해당 장소들을 선택 상태로 복원 |
| `action` | `save` | 페이지 로드 후 저장 플로우 자동 시작. 앱 웹뷰(MYPINPICK)에서는 자동 처리 후 홈 이동 |

## 웹뷰 User-Agent

앱 내 웹뷰의 User-Agent 문자열에 **`MYPINPICK`** 포함 필수.

```
예: Mozilla/5.0 ... Mobile Safari/537.36 MYPINPICK/1.0
```

웹에서 이 문자열로 앱 웹뷰 여부를 판별합니다:
- 앱 웹뷰: "앱으로 열기" 배너 숨김, 내부 네비게이션 처리
- 외부 브라우저: 딥링크 스킴 호출 시도

## 플랫폼별 등록

### iOS
- `Info.plist` → `CFBundleURLSchemes` 에 `pinpick` 추가
- `application(_:open:options:)` 에서 URL 전체(path + query) 추출 → 웹뷰 로드

### Android
- `AndroidManifest.xml` → intent-filter에 `pinpick` 스킴 등록
  ```xml
  <intent-filter>
      <action android:name="android.intent.action.VIEW" />
      <category android:name="android.intent.category.DEFAULT" />
      <category android:name="android.intent.category.BROWSABLE" />
      <data android:scheme="pinpick" />
  </intent-filter>
  ```
- `onCreate` 또는 `onNewIntent` 에서 `intent.data` 의 path + query 추출 → 웹뷰 로드

## 공유 저장 후 앱 전환 플로우

### 웹에서 로그인 상태로 저장 완료 → "핀픽에서 보기"
- 딥링크: `pinpick://` (홈 직행, action 파라미터 없음)
- 서버에 이미 저장됐으므로 앱에서 재저장 불필요

### 웹에서 게스트로 저장 → "핀픽에서 보기"
- 딥링크: `pinpick://s/{token}?selected=...&action=save` (기존 방식)
- 게스트 저장은 localStorage 기반이라 앱에서 재저장 필요

### 앱 웹뷰에서 action=save 자동 처리
MYPINPICK UA + `action=save`로 공유 페이지가 열리면 확인 화면 없이 자동 처리:
- **앱 로그인 + 새 장소 있음**: 카테고리 시트 → 저장 → 토스트 → 홈 자동 이동
- **앱 로그인 + 전부 중복**: "이미 저장된 장소예요" 토스트 → 홈 자동 이동
- **앱 비로그인**: 게스트 자동 저장 → 토스트 → 홈 자동 이동
- **일반 브라우저**: 기존 동작 유지 (변경 없음)

## 웹 호출 방식

| 환경 | 호출 방법 |
|------|-----------|
| Android 브라우저 | `intent://s/{token}?selected=1,2&action=save#Intent;scheme=pinpick;S.browser_fallback_url=https://mypinpick.net/s/{token};end` |
| iOS 브라우저 | `location.href = 'pinpick://s/{token}?selected=1,2&action=save'` + 1.5초 타임아웃 폴백 |
| 앱 웹뷰 | `location.href = '/s/{token}'` (일반 네비게이션) |
