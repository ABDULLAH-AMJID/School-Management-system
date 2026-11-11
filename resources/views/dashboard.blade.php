@extends('layouts.app',['title'=>'Dashboard'])
@section('content')
<div class="heading"><div><div class="eyebrow">{{auth()->user()->school->name}} · Staff workspace</div><h1>Welcome, {{str($userName=auth()->user()->name)->before(' ')}}</h1></div><span class="badge">{{auth()->user()->roles->pluck('label')->join(', ')}}</span></div>
<div class="metrics">@foreach($metrics as $metric)<div class="card metric"><div class="metric-top"><span>{{$metric['label']}}</span><span class="metric-icon">{{$metric['icon']==='users'?'◎':($metric['icon']==='check'?'✓':($metric['icon']==='wallet'?'₨':'◷'))}}</span></div><h2>{{$metric['value']}}</h2><div class="delta">{{$metric['delta']}}</div></div>@endforeach</div>
<div class="card panel" style="margin-top:18px"><div class="panel-title"><div><h3>Your workspace</h3><div class="eyebrow">Menus and dashboard information follow your assigned permissions.</div></div></div><p style="color:#667085;line-height:1.7">Use the navigation to access authorized school operations. Sensitive finance, payroll, publication, backup and access-control actions remain protected by separate permissions and approval workflows.</p></div>
@endsection
