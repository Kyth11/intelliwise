<!-- Add Tuition Modal -->
<div class="modal fade" id="addTuitionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('tuitions.store') }}" method="POST">
        @csrf

        <div class="modal-header">
          <h5 class="modal-title">Add Tuition & Fees</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">Grade Level</label>
            <select name="grade_level" class="form-select" required>
              <option value="">— Select Grade Level —</option>
              <option value="Nursery">Nursery</option>
              <option value="Kindergarten 1">Kindergarten 1</option>
              <option value="Kindergarten 2">Kindergarten 2</option>
              <option value="Grade 1">Grade 1</option>
              <option value="Grade 2">Grade 2</option>
              <option value="Grade 3">Grade 3</option>
              <option value="Grade 4">Grade 4</option>
              <option value="Grade 5">Grade 5</option>
              <option value="Grade 6">Grade 6</option>
            </select>
          </div>

          {{-- Tuition (monthly/yearly interchangeable) --}}
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label">Tuition (Monthly) ₱</label>
              <input type="number" step="0.01" min="0" name="tuition_monthly" id="add_tuition_monthly" class="form-control">
              <div class="form-text">Typing here auto-fills Yearly (×10).</div>
            </div>
            <div class="col-6">
              <label class="form-label">Tuition (Yearly) ₱</label>
              <input type="number" step="0.01" min="0" name="tuition_yearly" id="add_tuition_yearly" class="form-control">
              <div class="form-text">Typing here auto-fills Monthly (÷10).</div>
            </div>
          </div>

          {{-- Misc (monthly/yearly interchangeable) --}}
          <div class="row g-2 mt-2">
            <div class="col-6">
              <label class="form-label">Misc (Monthly) ₱ (optional)</label>
              <input type="number" step="0.01" min="0" name="misc_monthly" id="add_misc_monthly" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label">Misc (Yearly) ₱ (optional)</label>
              <input type="number" step="0.01" min="0" name="misc_yearly" id="add_misc_yearly" class="form-control">
            </div>
          </div>

          {{-- Books --}}
          <div class="row g-2 mt-2">
            <div class="col-7">
              <label class="form-label">Books (desc) — optional</label>
              <input type="text" name="books_desc" class="form-control" placeholder="e.g., Workbooks set">
            </div>
            <div class="col-5">
              <label class="form-label">Books Amount ₱ — optional</label>
              <input type="number" step="0.01" min="0" name="books_amount" id="add_books_amount" class="form-control">
            </div>
          </div>

          {{-- Grade-level Optional Fees --}}
          <div class="mt-3">
            <label class="form-label">Attach Optional Fees (Grade-level)</label>
            <div class="border rounded p-2" style="max-height: 180px; overflow:auto;">
              @forelse(($optionalFees ?? collect()) as $fee)
                @if($fee->scope === 'grade' || $fee->scope === 'both')
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="optfee_{{ $fee->id }}" name="optional_fee_ids[]" value="{{ $fee->id }}">
                    <label class="form-check-label" for="optfee_{{ $fee->id }}">
                      {{ $fee->name }} — ₱{{ number_format($fee->amount, 2) }}
                    </label>
                  </div>
                @endif
              @empty
                <div class="text-muted">No optional fees available. Add some in “Optional Fees”.</div>
              @endforelse
            </div>
          </div>

          {{-- School Year --}}
          <div class="mb-3 mt-3">
            <label class="form-label">School Year (optional)</label>
            <select name="school_year" class="form-select">
              <option value="">— None —</option>
              @foreach($schoolyrs as $sy)
                <option value="{{ $sy->school_year }}">{{ $sy->school_year }}</option>
              @endforeach
            </select>
          </div>

          {{-- Computed preview total --}}
          <div class="mb-2">
            <label class="form-label">Computed Total (preview) ₱</label>
            <input type="text" id="add_total_preview" class="form-control" readonly>
            <div class="form-text">= Tuition Yearly + Misc Yearly + Books + (Selected Optional Fees)</div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const MONTHS = 10;
  const tMon  = document.getElementById('add_tuition_monthly');
  const tYear = document.getElementById('add_tuition_yearly');
  const mMon  = document.getElementById('add_misc_monthly');
  const mYear = document.getElementById('add_misc_yearly');
  const books = document.getElementById('add_books_amount');
  const preview = document.getElementById('add_total_preview');

  function n(v){ const x=parseFloat(v); return isNaN(x)?0:x; }
  let lock=false;

  function fromTMon(){ if(lock) return; lock=true; tYear.value=(n(tMon.value)*MONTHS).toFixed(2); calc(); lock=false; }
  function fromTYear(){ if(lock) return; lock=true; tMon.value=(n(tYear.value)/MONTHS).toFixed(2); calc(); lock=false; }
  function fromMMon(){ if(lock) return; lock=true; mYear.value=(n(mMon.value)*MONTHS).toFixed(2); calc(); lock=false; }
  function fromMYear(){ if(lock) return; lock=true; mMon.value=(n(mYear.value)/MONTHS).toFixed(2); calc(); lock=false; }

  function selectedOptionalTotal() {
    let total = 0;
    document.querySelectorAll('#addTuitionModal input[name="optional_fee_ids[]"]:checked')
      .forEach(cb => {
        const label = document.querySelector('label[for="'+cb.id+'"]')?.innerText || '';
        const match = label.match(/₱([\d,]+(\.\d{1,2})?)/);
        if (match) total += parseFloat(match[1].replace(/,/g,''));
      });
    return total;
  }

  function calc(){
    const ty = n(tYear.value);
    const my = n(mYear.value);
    const b  = n(books.value);
    const opt = selectedOptionalTotal();
    preview.value = (ty + my + b + opt).toFixed(2);
  }

  tMon?.addEventListener('input', fromTMon);
  tYear?.addEventListener('input', fromTYear);
  mMon?.addEventListener('input', fromMMon);
  mYear?.addEventListener('input', fromMYear);
  books?.addEventListener('input', calc);
  document.querySelectorAll('#addTuitionModal input[name="optional_fee_ids[]"]').forEach(cb => cb.addEventListener('change', calc));

  calc();
});
</script>
