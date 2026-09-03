<?php
/**
 * RAG rang afsonasi (legend) — modullar bo'ylab qayta ishlatiladi.
 * green = talab bajarilgan, yellow = qisman, red = bajarilmagan/muammo,
 * grey = ma'lumot/dalil kiritilmagan (docs/05, item 3).
 *
 * @var \App\Core\View $this
 */
?>
<div class="rag-legend" role="list" aria-label="RAG holat afsonasi">
    <span class="badge badge-green" role="listitem">Yashil — talab bajarilgan</span>
    <span class="badge badge-yellow" role="listitem">Sariq — qisman bajarilgan</span>
    <span class="badge badge-red" role="listitem">Qizil — bajarilmagan / muammo</span>
    <span class="badge badge-grey" role="listitem">Kulrang — ma'lumot kiritilmagan</span>
</div>
