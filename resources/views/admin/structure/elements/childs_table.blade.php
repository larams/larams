<table class="table table-striped table-condensed table-sortable" data-url="{{url( 'admin/structure/sort/' . $currentItem->id )}}">
    <thead>
    <tr>
        @if (!empty( $sorting ))
            <th>&nbsp;</th>
        @endif
        <th>{{trans('admin.table_heading.title')}}</th>
        @foreach ( $extra_columns as $column => $columnTitle )
            <th>{{$columnTitle}}</th>
        @endforeach
        <th class="col-xs-1">{{trans('admin.table_heading.status')}}</th>
        <th class="col-xs-2">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    @foreach ( $childs as $item )
        <tr id="item_{{$item->id}}">
            @if (!empty( $sorting ))
                <td class="sort-column">
                    <a href="#">
                        <span class="fa fa-align-justify"></span>
                    </a>
                </td>
            @endif
            <td>
                <a href="{{url( '/admin/structure/index/' . $item->id )}}" title="{{$item->name}}">{{$item->name}}</a>
            </td>
            @foreach ( $extra_columns as $column => $columnTitle )
                <td>
                    @if (strpos( $column, 'data.') !== false)
                        @if ( property_exists( $item->data, substr( $column, 5 )))
                            {{ $item->data->{substr( $column, 5 )} }}
                        @endif
                    @else
                        @if (property_exists( $item, $column ))
                            {{$item->$column}}
                        @endif
                    @endif
                </td>
            @endforeach
            <td>
                <a class="label @if ($item->active == 1)label-success @else label-danger @endif " href="{{url( '/admin/structure/active/'. $currentItem->id .'/'. $item->id . '/' . $item->active )}}" title="@if ($item->active == 1) {{trans('admin.Hide element')}} @else {{trans('admin.Make visible')}} @endif">@if ($item->active == 1)
                        {{trans('admin.button.visible')}} @else {{trans('admin.button.hidden')}} @endif </a>
            </td>
            <td class="actions">
                <a href="{{url(  '/admin/structure/index/' . $item->id )}}" title="{{$item->name}}" class="btn btn-xs btn-default edit-row-link">{{trans('admin.button.edit')}}</a>
                &nbsp;<a href="{{url( '/admin/structure/delete/' . $currentItem->id . '/'. $item->id )}}" class="btn btn-xs btn-default delete-row-link">{{trans('admin.button.delete')}}</a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
