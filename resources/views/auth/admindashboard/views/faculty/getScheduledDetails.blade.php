<style>
       .days-list {
            display: flex;
            gap: 20px;
        }

        .day-checkbox {
            display: none;
        }

        .day-label {
            display: inline-block;
            width: 45px;
            height: 35px;
            text-align: center;
            line-height: 35px;
            border: 1px solid #ccc;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            color: #555;
            user-select: none;
        }

        .day-checkbox:checked + .day-label {
            background-color: #3b82f6; /* Tailwind's blue-500 */
            color: white;
            border-color: #3b82f6;
        }
</style>
<h4>Subjects</h4>
<div class="table-responsive">
    <table class="table table-hover table-bordered" id="mysubjectlist">
        <thead>
            <tr>
                <th>No.#</th>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Days of the Week</th>
                <th>Start Time </th>
                <th>End Time</th>
                <th>Faculty Name</th>
            </tr>
        </thead>
        <tbody>
        <?php if($child): ?>
            <?php $i=1;?>
            <?php foreach ($child as $value): ?>
                <tr>
                    <td><?=$i?><input type="hidden" value="<?= $value->id ?>" name="itemlist[data][old<?=$value->id?>][id]" ></td>
                    <td><?= $value->subject_code ?></td>
                    <td><?= $value->subject_name ?></td>
                    <td>
                        <div class="days-list">
                            <?php
                            $ex = explode('|', $value->day_schedule);
                            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            foreach ($days as $index => $day): ?>
                                <input type="checkbox" class="day-checkbox" name="itemlist[data][old<?=$value->id?>][day_schedule][]" id="day_<?= $i.$index ?>" value="<?= $day ?>"  <?= in_array($day, $ex) ? 'checked' : '' ?>>
                                <label for="day_<?= $i.$index ?>" class="day-label"><?= $day ?></label>
                            <?php endforeach; ?>
                        </div></td>
                    <td><input type="time" class="form-control" name="itemlist[data][old<?=$value->id?>][class_start]" value="<?= $value->class_start ?>" id=""></td>
                    <td><input type="time" class="form-control" name="itemlist[data][old<?=$value->id?>][class_end]" value="<?= $value->class_end ?>" id=""></td>

                    <td>
                         <select name="itemlist[data][old<?=$value->id?>][adviser_id]" class="form-select" >
                                <option value="">Select Faculty</option>
                                @foreach(($faculties ?? collect()) as $f)
                                @php $fname = optional($f->user)->name ?: ('Faculty #'.$f->id); @endphp
                                <option value="{{ $f->id }}" {{($f->id==$value->adviser_id)?'selected':''}} >
                                    {{ $fname }}
                                </option>
                                @endforeach
                        </select>
                    </td>

                </tr>
                <?php $i++;?>
            <?php endforeach; ?>
        <?php endif; ?>
        
        </tbody>
    </table>
    
</div>   