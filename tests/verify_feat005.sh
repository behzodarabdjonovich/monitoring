#!/usr/bin/env bash
# FEAT-005 jonli tekshiruv: dalil yuklash, 2 indikatorga bog'lash, ruxsatsiz
# yuklab olishni rad etish, fayl va havola bilan ilmiy natija yozish.
set -u
BASE="http://127.0.0.1:8000"
C=(curl -s --noproxy '*')
JAR=$(mktemp)
JAR2=$(mktemp)
TMPDIR_L=$(mktemp -d)

csrf() { echo "$1" | grep -o 'name="_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//; s/"//'; }

echo "== 1) Login (admin) =="
LOGIN_PAGE=$("${C[@]}" -c "$JAR" "$BASE/login")
TOKEN=$(csrf "$LOGIN_PAGE")
"${C[@]}" -c "$JAR" -b "$JAR" -o /dev/null -w "login POST status: %{http_code}\n" \
  -d "_token=$TOKEN" -d "username=admin" -d "password=Parol123!" "$BASE/login"

echo "== 2) Dalil PDF yuklash =="
DOCS_PAGE=$("${C[@]}" -b "$JAR" "$BASE/documents")
TOKEN=$(csrf "$DOCS_PAGE")
printf '%%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%%%EOF' > "$TMPDIR_L/evidence.pdf"
UP_HDR=$("${C[@]}" -b "$JAR" -D - -o /dev/null \
  -F "_token=$TOKEN" -F "title=Jonli dalil PDF" -F "category=buyruqlar" \
  -F "file=@$TMPDIR_L/evidence.pdf;type=application/pdf" "$BASE/documents")
DOC_URL=$(echo "$UP_HDR" | grep -i '^location' | tr -d '\r' | awk '{print $2}')
DOC_ID=$(echo "$DOC_URL" | grep -o '[0-9]*$')
echo "Yuklangan hujjat ID: $DOC_ID (redirect: $DOC_URL)"

echo "== 3) Ikki indikatorga bog'lash =="
SHOW_PAGE=$("${C[@]}" -b "$JAR" "$BASE/documents/$DOC_ID")
# HTML'ni bir qatorga yig'ib, link select'idagi option id'larni olamiz.
IND_IDS=$(echo "$SHOW_PAGE" | tr '\n' ' ' | grep -o 'id="indicator_id".*</select>' | grep -o 'value="[0-9]\+"' | grep -o '[0-9]\+' | head -2)
I1=$(echo "$IND_IDS" | sed -n '1p'); I2=$(echo "$IND_IDS" | sed -n '2p')
echo "Indikatorlar: $I1, $I2"
for I in $I1 $I2; do
  TOKEN=$(csrf "$("${C[@]}" -b "$JAR" "$BASE/documents/$DOC_ID")")
  "${C[@]}" -b "$JAR" -o /dev/null -w "link ind $I status: %{http_code}\n" \
    -d "_token=$TOKEN" -d "indicator_id=$I" -d "note=jonli" "$BASE/documents/$DOC_ID/link"
done

echo "== 4) Hujjat ikkala indikatorni ko'rsatadimi? =="
FINAL_DOC=$("${C[@]}" -b "$JAR" "$BASE/documents/$DOC_ID")
echo "Bog'langan indikatorlar (Uzish tugmalari) soni: $(echo "$FINAL_DOC" | grep -c 'Uzish')"

echo "== 5) Himoyalangan yuklab olish (ruxsatli admin) =="
"${C[@]}" -b "$JAR" -o "$TMPDIR_L/dl.pdf" -w "download (admin) status: %{http_code}, size: %{size_download}\n" "$BASE/documents/$DOC_ID/download"

echo "== 6) Ruxsatsiz yuklab olish (login qilinmagan sessiya) — rad etilishi kerak =="
ANON_HDR=$("${C[@]}" -c "$JAR2" -b "$JAR2" -D - -o "$TMPDIR_L/anon.out" -w "download (anon) status: %{http_code}\n" "$BASE/documents/$DOC_ID/download")
echo "$ANON_HDR" | grep -i '^location' | tr -d '\r'
echo "anon javob PDF EMAS (fayl mazmuni berilmadi): $(grep -c '%PDF' "$TMPDIR_L/anon.out" 2>/dev/null || echo 0)"

echo "== 7) Ilmiy natija: fayl bilan (Scopus) =="
RES_FORM=$("${C[@]}" -b "$JAR" "$BASE/results/create")
TOKEN=$(csrf "$RES_FORM")
SID=$(echo "$RES_FORM" | tr '\n' ' ' | grep -o 'id="student_id".*id="supervisor_id"' | grep -o 'value="[0-9]\+"' | grep -o '[0-9]\+' | head -1)
printf '%%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%%%EOF' > "$TMPDIR_L/res.pdf"
"${C[@]}" -b "$JAR" -o /dev/null -w "result(file) status: %{http_code}\n" \
  -F "_token=$TOKEN" -F "result_type=scopus_maqola" -F "title=Jonli Scopus maqola" \
  -F "student_id=$SID" -F "achieved_at=2024-05-01" \
  -F "evidence_file=@$TMPDIR_L/res.pdf;type=application/pdf" "$BASE/results"

echo "== 8) Ilmiy natija: havola bilan (grant) =="
TOKEN=$(csrf "$("${C[@]}" -b "$JAR" "$BASE/results/create")")
"${C[@]}" -b "$JAR" -o /dev/null -w "result(url) status: %{http_code}\n" \
  -d "_token=$TOKEN" -d "result_type=grant" -d "title=Jonli grant" \
  -d "student_id=$SID" --data-urlencode "url=https://example.org/grant" "$BASE/results"

echo "== 9) Natijalar ro'yxatida ikkalasi ko'rinadimi? =="
RES_LIST=$("${C[@]}" -b "$JAR" "$BASE/results")
echo "Scopus qatori: $(echo "$RES_LIST" | grep -c 'Jonli Scopus maqola')"
echo "Grant qatori: $(echo "$RES_LIST" | grep -c 'Jonli grant')"
echo "Fayl (download) havolasi soni: $(echo "$RES_LIST" | grep -c '/download')"
echo "Havola (external) soni: $(echo "$RES_LIST" | grep -c 'example.org/grant')"

rm -rf "$JAR" "$JAR2" "$TMPDIR_L"
