<!-- Add Tuition Modal -->
<div class="modal fade" id="addTuitionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            {{-- 🔽 use the plural route name --}}
            <form action="{{ route('tuitions.store') }}" method="POST"> {{-- not tuition.store --}}


                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Tuition & Fees</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- Flash messages --}}
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Grade Level -->
                    <div class="mb-3">
                        <label class="form-label">Grade Level</label>
                        <select name="grade_level" id="grade_level" class="form-select" required>
                            <option value="">— Select Grade Level —</option>
                            <option value="Pre-Schooler">Pre-Schooler</option>
                            <option value="Nursery">Nursery</option>
                            <option value="Kindergarten">Kindergarten</option>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                        </select>
                    </div>

                    <!-- Monthly / Yearly (interchangeable) -->
                    <div class="mb-3">
                        <label class="form-label">Monthly Tuition (₱)</label>
                        <input type="number" step="0.01" min="0" name="monthly_fee" id="monthly_fee"
                            class="form-control" placeholder="e.g., 2500.00">
                        <div class="form-text">Typing here auto-calculates School Year Tuition as ×10.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">School Year Tuition (₱) — 10 months</label>
                        <input type="number" step="0.01" min="0" name="yearly_fee" id="yearly_fee" class="form-control"
                            placeholder="e.g., 25000.00">
                        <div class="form-text">Typing here auto-calculates Monthly Tuition as ÷10.</div>
                    </div>

                    <!-- Misc (optional) -->
                    <div class="mb-3">
                        <label class="form-label">Miscellaneous Fee (₱) — optional</label>
                        <input type="number" step="0.01" min="0" name="misc_fee" id="misc_fee" class="form-control"
                            placeholder="Leave blank if none">
                    </div>

                    <!-- Optional fee -->
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label">Optional Fee Description (optional)</label>
                            <input type="text" name="optional_fee_desc" id="optional_fee_desc" class="form-control"
                                placeholder="e.g., ID / Lab / Uniform">
                        </div>
                        <div class="col-5">
                            <label class="form-label">Optional Fee Amount (₱) — optional</label>
                            <input type="number" step="0.01" min="0" name="optional_fee_amount" id="optional_fee_amount"
                                class="form-control" placeholder="0.00">
                        </div>
                    </div>

                    <!-- School Year (optional) -->
                    <div class="mb-3 mt-3">
                        <label class="form-label">School Year (optional)</label>
                        <select name="school_year" class="form-select">
                            <option value="">— None —</option>
                            @foreach($schoolyrs as $sy)
                                <option value="{{ $sy->school_year }}" {{ old('school_year') === $sy->school_year ? 'selected' : '' }}>
                                    {{ $sy->school_year }}
                                </option>
                            @endforeach
                        </select>

                    </div>

                    <!-- Computed total -->
                    <div class="mb-3">
                        <label class="form-label">Total (Whole School Year, ₱)</label>
                        <input type="number" step="0.01" name="total_yearly" id="total_yearly" class="form-control"
                            readonly>
                        <div class="form-text">= Yearly Tuition + Misc (if any) + Optional (if any)</div>
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
        const monthly = document.getElementById('monthly_fee');
        const yearly = document.getElementById('yearly_fee');
        const misc = document.getElementById('misc_fee');
        const optAmt = document.getElementById('optional_fee_amount');
        const total = document.getElementById('total_yearly');

        let isSyncing = false;
        const num = v => (isNaN(parseFloat(v)) ? 0 : parseFloat(v));

        function syncFromMonthly() {
            if (isSyncing) return; isSyncing = true;
            yearly.value = (num(monthly.value) * MONTHS).toFixed(2);
            recalcTotal();
            isSyncing = false;
        }

        function syncFromYearly() {
            if (isSyncing) return; isSyncing = true;
            monthly.value = (num(yearly.value) / MONTHS).toFixed(2);
            recalcTotal();
            isSyncing = false;
        }

        function recalcTotal() {
            const y = num(yearly.value);
            const m = num(misc.value);
            const o = num(optAmt.value);
            total.value = (y + m + o).toFixed(2);
        }

        monthly?.addEventListener('input', syncFromMonthly);
        yearly?.addEventListener('input', syncFromYearly);
        misc?.addEventListener('input', recalcTotal);
        optAmt?.addEventListener('input', recalcTotal);

        recalcTotal();
    });
</script>
