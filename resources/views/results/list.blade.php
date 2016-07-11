@if(isset($title) && $title)<h2>{{$title}}</h2>@endif
@if(count($results) === 0)
    <p>No tests found.</p>
@else
    <table class="table table-striped table-hover table-condensed">
        <tr>
            <th>Developer</th>
            <th>Tests</th>
        </tr>
        @foreach($results as $row)
            <tr>
                <td>
                    <a href="{{ route('user', ['user' => $row->author_slug]) }}@if(isset($months_back) && isset($type) && $type == 'from')?fromMonthsBack={{$months_back}}@elseif(isset($months_back))?monthsBack={{$months_back}}@endif">
                        {{$row->author}}
                    </a>
                </td>
                <td>{{$row->score}}</td>
            </tr>
        @endforeach
    </table>
@endif