@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://imgur.com/a/vTD8n2R" class="logo" alt="Bylin Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
