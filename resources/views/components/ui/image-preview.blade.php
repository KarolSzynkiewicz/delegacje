@props([
    'inputId' => 'image',
    'previewId' => 'imagePreview',
    'imgId' => 'previewImg',
    'currentImage' => null,
    'currentImageUrl' => null,
    'showCurrentImage' => false,
])

<div class="mb-3">
    @if($showCurrentImage && $currentImageUrl)
        <label class="form-label">Zdjęcie</label>
        <div class="mb-2">
            <p class="text-muted small">Aktualne zdjęcie:</p>
            <img src="{{ $currentImageUrl }}" alt="{{ $currentImage ?? 'Aktualne zdjęcie' }}" class="img-thumbnail" style="max-width: 300px;">
        </div>
    @else
        <x-ui.input 
            type="file" 
            name="image" 
            label="Zdjęcie"
            accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
            :id="$inputId"
        />
    @endif
    
    @if($showCurrentImage && $currentImageUrl)
        <x-ui.input 
            type="file" 
            name="image" 
            accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
            :id="$inputId"
        />
        <small class="form-text text-muted">Maksymalny rozmiar: 2MB. Dozwolone formaty: JPEG, PNG, JPG, GIF, WEBP. Zostaw puste, aby zachować obecne zdjęcie.</small>
    @else
        <small class="form-text text-muted">Maksymalny rozmiar: 2MB. Dozwolone formaty: JPEG, PNG, JPG, GIF, WEBP</small>
    @endif
    
    <div id="{{ $previewId }}" class="mt-3" style="display: none;">
        @if($showCurrentImage && $currentImageUrl)
            <p class="text-muted small">Nowe zdjęcie:</p>
        @endif
        <img id="{{ $imgId }}" src="" alt="Podgląd" class="img-thumbnail" style="max-width: 300px;">
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('{{ $inputId }}');
        const preview = document.getElementById('{{ $previewId }}');
        const img = document.getElementById('{{ $imgId }}');
        
        if (input && preview && img) {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                        preview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
                }
            });
        }
    });
</script>
@endpush
