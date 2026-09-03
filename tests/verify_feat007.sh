#!/bin/bash
# FEAT-007 runtime verifikatsiyasi (item 12 + item 13).
# Server ishga tushiradi, admin sifatida kiradi va:
#   1) kamchilik + muddati yaqin (sariq) va muddati o'tgan (qizil) chora-tadbir yaratadi;
#   2) ixtisoslik bo'yicha ichki audit o'tkazadi va hisobot bo'limlarini tekshiradi.
# Ishga tushirish: bash tests/verify_feat007.sh
set -e
cd "$(dirname "$0")/.."
php -S 127.0.0.1:8138 -t public public/index.php >/tmp/srv.log 2>&1 &
SRV=$!
trap "kill $SRV 2>/dev/null" EXIT
sleep 2
CJ=/tmp/cj.txt; rm -f $CJ
B='http://127.0.0.1:8138'
curl -s --noproxy '*' -o /dev/null -w "probe /login: %{http_code}\n" "$B/login"
curl -s --noproxy '*' -c $CJ "$B/login" -o /tmp/login.html
TOKEN=$(grep -oE 'name="_token" value="[^"]+"' /tmp/login.html | head -1 | sed -E 's/.*value="([^"]+)".*/\1/')
curl -s --noproxy '*' -b $CJ -c $CJ -X POST "$B/login" --data-urlencode "_token=$TOKEN" --data-urlencode "username=admin" --data-urlencode "password=Parol123!" -o /dev/null -w "login: %{http_code}\n"
curl -s --noproxy '*' -b $CJ -c $CJ "$B/deficiencies" -o /tmp/def.html -w "deficiencies GET: %{http_code}\n"
TOKEN=$(grep -oE 'name="_token" value="[^"]+"' /tmp/def.html | head -1 | sed -E 's/.*value="([^"]+)".*/\1/')
curl -s --noproxy '*' -b $CJ -c $CJ -X POST "$B/deficiencies" --data-urlencode "_token=$TOKEN" --data-urlencode "title=Curl muammo" --data-urlencode "cause=Curl sabab" --data-urlencode "severity=high" -o /dev/null -D /tmp/h.txt -w "create def: %{http_code}\n"
LOC=$(grep -i '^location:' /tmp/h.txt | tr -d '\r' | awk '{print $2}')
DEFID=$(echo "$LOC" | grep -oE '[0-9]+$')
echo "created deficiency id=$DEFID"
curl -s --noproxy '*' -b $CJ -c $CJ "$B/deficiencies/$DEFID" -o /tmp/defshow.html
TOKEN=$(grep -oE 'name="_token" value="[^"]+"' /tmp/defshow.html | head -1 | sed -E 's/.*value="([^"]+)".*/\1/')
NEAR=$(date -d '+3 days' +%Y-%m-%d)
OVER=$(date -d '-5 days' +%Y-%m-%d)
curl -s --noproxy '*' -b $CJ -c $CJ -X POST "$B/deficiencies/$DEFID/plans" --data-urlencode "_token=$TOKEN" --data-urlencode "title=Yaqin chora" --data-urlencode "due_date=$NEAR" -o /dev/null -w "add near plan (yellow): %{http_code}\n"
curl -s --noproxy '*' -b $CJ -c $CJ -X POST "$B/deficiencies/$DEFID/plans" --data-urlencode "_token=$TOKEN" --data-urlencode "title=Otgan chora" --data-urlencode "due_date=$OVER" -o /dev/null -w "add overdue plan (red): %{http_code}\n"
curl -s --noproxy '*' -b $CJ -c $CJ "$B/deficiencies/$DEFID" -o /tmp/defshow2.html
echo "-- due row classes:"; grep -oE 'due-(due_soon|overdue|done|normal)' /tmp/defshow2.html | sort | uniq -c
echo "-- coloring badges:"; grep -oE 'badge-(yellow|red|green)">(Muddati yaqin|Muddati o[^<]*tgan|Bajarilgan)' /tmp/defshow2.html | sort | uniq -c
curl -s --noproxy '*' -b $CJ -c $CJ "$B/audits" -o /tmp/audits.html -w "audits GET: %{http_code}\n"
SPEC=$(grep -oE '<option value="[0-9]+">[^<]' /tmp/audits.html | head -1 | grep -oE '[0-9]+')
echo "specialty id=$SPEC"
TOKEN=$(grep -oE 'name="_token" value="[^"]+"' /tmp/audits.html | head -1 | sed -E 's/.*value="([^"]+)".*/\1/')
curl -s --noproxy '*' -b $CJ -c $CJ -X POST "$B/audits/run" --data-urlencode "_token=$TOKEN" --data-urlencode "specialty_id=$SPEC" -o /dev/null -D /tmp/ah.txt -w "run audit: %{http_code}\n"
ALOC=$(grep -i '^location:' /tmp/ah.txt | tr -d '\r' | awk '{print $2}')
echo "audit -> $ALOC"
curl -s --noproxy '*' -b $CJ -c $CJ "$B$ALOC" -o /tmp/auditshow.html -w "audit report GET: %{http_code}\n"
echo "-- audit report sections present:"
for s in "Kuchli tomonlar" "Kamchiliklar" "Bajarilmagan indikatorlar" "Yetishmayotgan dalillar" "Xavf darajasi" "Tavsiyalar" "Chora-tadbirlar rejasi" "tayyorlik foizi"; do
  if grep -q "$s" /tmp/auditshow.html; then echo "  [OK] $s"; else echo "  [MISSING] $s"; fi
done
