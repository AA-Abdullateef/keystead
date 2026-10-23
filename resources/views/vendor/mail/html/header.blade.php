@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<span style="font-family: Georgia, 'Times New Roman', serif; font-size: 24px; font-weight: 600; color: #1f4d3a; letter-spacing: .01em;">&#9632; {!! $slot !!}</span>
</a>
</td>
</tr>
