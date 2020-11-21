<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'AtariLegend')
<img src="{{ asset('images/class_al/class.png') }}" class="logo" alt="AtariLegend Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
