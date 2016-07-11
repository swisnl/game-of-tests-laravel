<table class="table table-striped table-hover table-condensed">
    @foreach($results as $key => $row)
        @if($key == 0)
            <h3>{{$row['author_normalized']}}</h3>
        @endif
        <tr>
            <td>{{ $row['filename'] }}:{{ $row['line'] }}</td>
            <td>{{ $row['created_at'] }}</td>
            <td>{{ $row['parser'] }}<br>
                <a href="{{ $row->getUrl() }}"
                   target="_blank">{{ $row['remote'] }}</a>
            </td>
        </tr>
    @endforeach
</table>