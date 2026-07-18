<?php
declare(strict_types=1);
?>
<script>
function wrapText(textareaId, before, after = '') {
    const textarea = document.getElementById(textareaId);
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.substring(start, end);
    const replacement = before + selected + after;

    textarea.setRangeText(replacement, start, end, 'end');
    textarea.focus();
}

function insertLine(textareaId, text) {
    const textarea = document.getElementById(textareaId);
    if (!textarea) return;

    const pos = textarea.selectionStart;
    textarea.setRangeText(text, pos, pos, 'end');
    textarea.focus();
}
</script>

<style>
.rich-toolbar{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:10px;
}
.rich-toolbar button{
    border:1px solid #e8defd;
    background:#fff;
    color:#6d28d9;
    padding:8px 12px;
    border-radius:10px;
    font-weight:700;
    cursor:pointer;
}
.rich-toolbar button:hover{
    background:#f7f1ff;
}
</style>