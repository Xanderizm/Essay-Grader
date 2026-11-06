<form method="POST" action="{{ route('answers.override', $a) }}" style="margin-top:10px">
    @csrf
    <div class="muted">Koreksi nilai (0-100): </div>
    <div style="display:flex; gap:6px; margin-top:4px">
        <label style="flex:1">K <input type="number" name="score_content" min="0" max="100" value="{{ $a->score_content ?? 0 }}" required></label>
        <label style="flex:1">L <input type="number" name="score_logic" min="0" max="100" value="{{ $a->score_logic ?? 0 }}" required></label>
        <label style="flex:1">S <input type="number" name="score_structure" min="0" max="100" value="{{ $a->score_structure ?? 0 }}" required></label>
    </div>
    <textarea name="override_note" placeholder="Catatan (opsional)" style="min-height:50px">{{ $a->override_note }}</textarea>
    <div class="row-actions">
        <button type="submit">Simpan koreksi</button>
    </div>
</form>

@if($a->is_overridden)
    <form method="POST" action="{{ route('answers.revert', $a) }}" style="margin-top:6px"
          data-confirm="Kembalikan ke nilai AI? Koreksi pembuat akan dibuang.">
        @csrf
        <button type="submit" class="danger">Kembalikan ke nilai AI</button>
    </form>
@endif