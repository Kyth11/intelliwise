@extends('layouts.faculty')
@section('title','IGCA - Faculty Dashboard')

@section('content')
  <div class="row">
    <div class="col-md-4 mb-4"><div class="card p-4 text-center"><h5>Assignments</h5><h2>3</h2></div></div>
    <div class="col-md-4 mb-4"><div class="card p-4 text-center"><h5>Students</h5><h2>8</h2></div></div>
    <div class="col-md-4 mb-4"><div class="card p-4 text-center"><h5>Messages</h5><h2>5</h2></div></div>
  </div>
  <div class="card p-4">
    <h5>Upcoming Activities</h5>
    <ul class="mb-0">
      <li>Math Examination – March 2, 2026</li>
      <li>Foundation Day – Feb 20, 2026</li>
      <li>PTA Meeting – Feb 15, 2026</li>
    </ul>
  </div>
@endsection
