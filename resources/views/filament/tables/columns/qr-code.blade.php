@php
    $url = url('/api/products/' . $getRecord()->id);
@endphp
<style>
    .qr-modal-bg {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .qr-modal-content {
        background: #fff;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.2);
        text-align: center;
        max-width: 90vw;
        max-height: 90vh;
    }

    .qr-modal-content img,
    .qr-modal-content svg {
        width: 240px !important;
        height: 240px !important;
    }

    .qr-modal-close {
        position: absolute;
        top: 1.5rem;
        right: 2rem;
        font-size: 2rem;
        color: #333;
        cursor: pointer;
        z-index: 10000;
    }
</style>
<div style="display: flex; flex-direction: column; align-items: center;">
    <a href="#" onclick="event.preventDefault(); showQrModal_{{ $getRecord()->id }}();" title="Zoom QR">
        <span id="qr-small-{{ $getRecord()->id }}">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(60)->generate($url) !!}</span>
    </a>
    <a href="#" onclick="event.preventDefault(); showQrModal_{{ $getRecord()->id }}();"
        style="font-size: 0.8em; margin-top: 2px; color: #3490dc; text-decoration: underline;">Zoom Besar</a>
</div>
<div id="qr-modal-{{ $getRecord()->id }}" class="qr-modal-bg" style="display:none;">
    <div class="qr-modal-content" style="position:relative;">
        <span class="qr-modal-close" onclick="closeQrModal_{{ $getRecord()->id }}()">&times;</span>
        <div>{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(240)->generate($url) !!}</div>
    </div>
</div>
<script>
    function showQrModal_{{ $getRecord()->id }}() {
        document.getElementById('qr-modal-{{ $getRecord()->id }}').style.display = 'flex';
    }

    function closeQrModal_{{ $getRecord()->id }}() {
        document.getElementById('qr-modal-{{ $getRecord()->id }}').style.display = 'none';
    }
    // Optional: close modal on background click
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('qr-modal-{{ $getRecord()->id }}');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeQrModal_{{ $getRecord()->id }}();
            });
        }
    });
</script>
