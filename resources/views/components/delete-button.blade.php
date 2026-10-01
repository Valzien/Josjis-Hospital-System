@props([
    'message' => 'Apakah Anda yakin ingin menghapus data ini?',
    'title' => 'Konfirmasi Penghapusan',
    'confirmText' => 'Ya, Hapus',
    'action' => null,
    'variant' => 'danger',
    'icon' => 'bi-trash3',
])

<button type="button"
        class="jhs-action-btn danger"
        data-confirm='@json(["title" => $title, "message" => $message, "confirmText" => $confirmText, "variant" => $variant, "icon" => $icon, "url" => $action])'
        {{ $attributes }}
        title="{{ $title }}"
        aria-label="{{ $title }}">
    <i class="bi bi-trash3"></i>
</button>
