@if(isset($t))
<div class="modal fade" id="editTuitionModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('tuitions.update', $t->id) }}" method="POST" id="editTuitionForm{{ $t->id }}">
        @csrf
        @method('PUT')

        <div class="modal-header">
          <h5 class="modal-title">Edit Tuition & Fees</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label>Grade Level</label>
            <input type="text" name="grade_level" class="form-control" value="{{ old('grade_level', $t->grade_level) }}" required>
          </div>

          <div class="row g-2">
            <div class="col-6">
              <label>Tuition (Monthly) ₱</label>
              <input type="number" step="0.01" name="tuition_monthly" id="edit_tmon_{{ $t->id }}" class="form-control"
                     value="{{ old('tuition_monthly', number_format((float)$t->tuition_monthly, 2, '.', '')) }}">
            </div>
            <div class="col-6">
              <label>Tuition (Yearly) ₱</label>
              <input type="number" step="0.01" name="tuition_yearly" id="edit_tyear_{{ $t->id }}" class="form-control"
                     value="{{ old('tuition_yearly', number_format((float)$t->tuition_yearly, 2, '.', '')) }}">
            </div>
          </div>
          <small class="text-muted d-block mb-2">Monthly/Yearly auto-sync (10 months).</small>

          <div class="row g-2">
            <div class="col-6">
              <label>Misc (Monthly) ₱ (optional)</label>
              <input type="number" step="0.01" name="misc_monthly" id="edit_mmon_{{ $t->id }}" class="form-control"
                     value="{{ old('misc_monthly', is_null($t->misc_monthly) ? '' : number_format((float)$t->misc_monthly, 2, '.', '')) }}">
            </div>
            <div class="col-6">
              <label>Misc (Yearly) ₱ (optional)</label>
              <input type="number" step="0.01" name="misc_yearly" id="edit_myear_{{ $t->id }}" class="form-control"
                     value="{{ old('misc_yearly', is_null($t->misc_yearly) ? '' : number_format((float)$t->misc_yearly, 2, '.', '')) }}">
            </div>
          </div>

          <div class="row g-2 mt-2">
            <div class="col-5">
              <label>Books Amount ₱ (optional)</label>
              <input type="number" step="0.01" name="books_amount" id="edit_books_{{ $t->id }}" class="form-control"
                     value="{{ old('books_amount', is_null($t->books_amount) ? '' : number_format((float)$t->books_amount, 2, '.', '')) }}">
            </div>
          </div>

          {{-- Grade-level Optional Fees --}}
          <div class="mt-3">
            <label class="form-label">Attach Optional Fees (Grade-level)</label>
            @php $attached = $t->optionalFees->pluck('id')->all(); @endphp
            <div class="border rounded p-2" style="max-height: 180px; overflow:auto;">
              @forelse(($optionalFees ?? collect()) as $fee)
                @if($fee->scope === 'grade' || $fee->scope === 'both')
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           id="edit_optfee_{{ $t->id }}_{{ $fee->id }}"
                           name="optional_fee_ids[]"
                           value="{{ $fee->id }}"
                           {{ in_array($fee->id, $attached) ? 'checked' : '' }}>
                    <label class="form-check-label" for="edit_optfee_{{ $t->id }}_{{ $fee->id }}">
                      {{ $fee->name }} — ₱{{ number_format($fee->amount, 2) }}
                    </label>
                  </div>
                @endif
              @empty
                <div class="text-muted">No optional fees available. Add some in “Optional Fees”.</div>
              @endforelse
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label>School Year (optional)</label>
            <select name="school_year" class="form-select">
              <option value="">— None —</option>
              @foreach($schoolyrs as $sy)
                <option value="{{ $sy->school_year }}" {{ (old('school_year', $t->school_year) === $sy->school_year) ? 'selected' : '' }}>
                  {{ $sy->school_year }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label>Total (computed)</label>
            <input type="number" step="0.01" id="edit_total_{{ $t->id }}" class="form-control"
                   value="{{ number_format((float) $t->total_yearly, 2, '.', '') }}" readonly>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-warning">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const MONTHS = 10;
  const tmon  = document.getElementById('edit_tmon_{{ $t->id }}');
  const tyear = document.getElementById('edit_tyear_{{ $t->id }}');
  const mmon  = document.getElementById('edit_mmon_{{ $t->id }}');
  const myear = document.getElementById('edit_myear_{{ $t->id }}');
  const books = document.getElementById('edit_books_{{ $t->id }}');
  const total = document.getElementById('edit_total_{{ $t->id }}');

  function n(v){ const x=parseFloat(v); return isNaN(x)?0:x; }
  let lock=false;

  function syncFromMonthly(){ if(lock) return; lock=true; if(tyear) tyear.value=(n(tmon.value)*MONTHS).toFixed(2); sum(); lock=false; }
  function syncFromYearly(){ if(lock) return; lock=true; if(tmon)  tmon.value =(n(tyear.value)/MONTHS).toFixed(2); sum(); lock=false; }
  function miscFromMonthly(){ if(lock) return; lock=true; if(myear) myear.value=(n(mmon.value)*MONTHS).toFixed(2); sum(); lock=false; }
  function miscFromYearly(){ if(lock) return; lock=true; if(mmon)  mmon.value =(n(myear.value)/MONTHS).toFixed(2); sum(); lock=false; }

  function selectedOptionalTotal(){
    let s = 0;
    document.querySelectorAll('#editTuitionModal{{ $t->id }} input[name="optional_fee_ids[]"]:checked').forEach(cb => {
      const label = document.querySelector('label[for="'+cb.id+'"]')?.innerText || '';
      const m = label.match(/₱([\d,]+(\.\d{1,2})?)/);
      if (m) s += parseFloat(m[1].replace(/,/g,''));
    });
    return s;
  }

  function sum(){
    const ty = n(tyear?.value);
    const my = n(myear?.value);
    const b  = n(books?.value);
    const opt = selectedOptionalTotal();
    if(total) total.value = (ty + my + b + opt).toFixed(2);
  }

  tmon?.addEventListener('input', syncFromMonthly);
  tyear?.addEventListener('input', syncFromYearly);
  mmon?.addEventListener('input', miscFromMonthly);
  myear?.addEventListener('input', miscFromYearly);
  books?.addEventListener('input', sum);
  document.querySelectorAll('#editTuitionModal{{ $t->id }} input[name="optional_fee_ids[]"]').forEach(cb => cb.addEventListener('change', sum));

  sum();
});
</script>
@endif
