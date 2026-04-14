#!/usr/bin/env bash
# 핀픽 배포/캐시 재빌드 스크립트
# 사용법:
#   ./deploy.sh          # git pull + 전체 재빌드 (기본)
#   ./deploy.sh --no-pull  # git pull 생략 (로컬 수정만 반영)
#   ./deploy.sh --quick    # view/config만 재빌드 (route/event 건너뜀)
#   ./deploy.sh --migrate  # 마이그레이션 포함

set -euo pipefail

cd "$(dirname "$0")"

DO_PULL=1
DO_MIGRATE=0
QUICK=0

for arg in "$@"; do
    case "$arg" in
        --no-pull) DO_PULL=0 ;;
        --quick) QUICK=1 ;;
        --migrate) DO_MIGRATE=1 ;;
        *) echo "unknown option: $arg" >&2; exit 1 ;;
    esac
done

log() { echo -e "\033[1;36m▶ $*\033[0m"; }
ok()  { echo -e "\033[1;32m✓ $*\033[0m"; }

START=$(date +%s)

if [ "$DO_PULL" = "1" ]; then
    log "git pull"
    git pull --ff-only
fi

if [ "$DO_MIGRATE" = "1" ]; then
    log "migrate"
    php artisan migrate --force
fi

log "캐시 비우기"
php artisan view:clear     >/dev/null
php artisan config:clear   >/dev/null
if [ "$QUICK" = "0" ]; then
    php artisan route:clear >/dev/null
    php artisan event:clear >/dev/null
fi

log "캐시 재빌드 (프로덕션 최적화)"
php artisan config:cache >/dev/null
php artisan view:cache   >/dev/null
if [ "$QUICK" = "0" ]; then
    php artisan route:cache >/dev/null
    php artisan event:cache >/dev/null
fi

log "PHP-FPM 재시작 (OPcache 초기화)"
sudo systemctl reload php8.3-fpm

log "storage 심볼릭 링크 확인"
if [ ! -L public/storage ]; then
    php artisan storage:link
fi

ELAPSED=$(( $(date +%s) - START ))
ok "완료 (${ELAPSED}s)"
