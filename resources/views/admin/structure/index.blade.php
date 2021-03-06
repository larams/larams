<div class="row">
    <div class="col-md-4 col-lg-3 sidebar">

        <div class="mt20">

            @if ( count( $topLevelItems ) > 1)
                <div class="btn-group" style="width: 100%; padding: 0 20px;">
                    <button type="button" class="btn btn-default btn-block dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span style="float: left;">{{$currentTopLevelItem->name}}</span> <span style="float: right; margin-top: 7px;" class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" style="width: 100%">
                        @foreach ( $topLevelItems as $topLevelItem )
                            @if($topLevelItem->id != $currentTopLevelItem->id)
                                <li @if ( $topLevelItem->id == $currentLanguage->id ) class="active" @endif>
                                    <a href="{{url('admin/structure/index/' . $topLevelItem->id )}}">{{$topLevelItem->name}}</a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ( count( $languages) )
                <ul class="nav nav-pills mt20">
                    @foreach ( $languages as $language )
                        <li @if ( $language->id == $currentLanguage->id ) class="active" @endif>
                            <a href="{{url('admin/structure/index/' . $language->id )}}">{{$language->name}}</a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div id="structure-tree" class="mt20">
                <ul></ul>
            </div>
            <script>
                $(function () {
                    $('#structure-tree').jstree({
                        core: {
                            "check_callback": true,
                            data: {
                                url: '{{url('admin/structure/tree/' . $currentLanguage->id )}}'
                            }
                        },
                        state: {
                            key: 'structure'
                        },
                        types: {
                            'default': {
                                icon: "fa fa-folder text-yellow icon-lg"
                            },
                            file: {
                                icon: "fa fa-file-o icon-lg"
                            }
                        },
                        plugins: ['wholerow', 'state', 'types', 'dnd']
                    }).on('move_node.jstree', function (e, data) {

                        $.post('{{url('admin/structure/move')}}', {
                            id: data.node.id,
                            parent: data.parent,
                            position: data.position,
                            old_parent: data.old_parent,
                            old_position: data.old_position
                        });

                    }).on('activate_node.jstree', function (e, data) {

                        $('#loader').show();

                        document.location.href = '{{url('admin/structure/index')}}/' + data.node.id;

                    });
                });
            </script>
        </div>
    </div><!--/span-->
    <div class="col-md-8 col-md-offset-4 col-lg-9 col-lg-offset-3 content-area">
        <div class="mt20 mb20">
            @if ( count( $currentPath )  > 1)
                <ul class="breadcrumb">
                    @foreach ( $currentPath as $pathItem )
                        @if ($pathItem->id == $currentItem->id)
                            <li class="active">{{$pathItem->name}}</li>
                        @else
                            <li>
                                @if ($pathItem->level > 2 || !empty( $isDeveloper ))
                                    <a href="{{url( 'admin/structure/index/' . $pathItem->id )}}">{{$pathItem->name}}</a>
                                @else
                                    {{$pathItem->name}}
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif

            <h2 class="pull-left">{{$currentItem->name}}</h2>
            <div class="clearfix"></div>

            @if ( count( $treeChilds ) || count( $treeTypes ))
                <div class="well">
                    @if ( count( $treeChilds ) )
                        @if ( !empty($typeConfiguration['child_tree_items_list_title']) )
                            <h3>{{$typeConfiguration['child_tree_items_list_title']}}</h3>
                        @endif
                        @include('larams::admin.structure.elements.childs_table', ['childs' => $treeChilds, 'sorting' => !empty( $typeConfiguration['child_tree_item_sorting'] ), 'extra_columns' => $typeConfiguration['child_tree_extra_columns'] ] )

                    @endif

                    @if ( count( $treeTypes ) )
                        @include('larams::admin.structure.elements.childs_form', [ 'types' => $treeTypes, 'tree' => 1, 'title' => !empty( $typeConfiguration['child_tree_item_create_title'] ) ? $typeConfiguration['child_tree_item_create_title'] : trans('admin.button.add_new_tree_item') ] )
                    @endif
                </div>
            @endif

            @if ( count( $extraChilds ) || count( $extraTypes ) )
                <div class="well">
                    @if ( count( $extraChilds ) )
                        @if ( !empty($typeConfiguration['child_items_list_title']) )
                            <h3>{{ $typeConfiguration['child_items_list_title']}}</h3>
                        @endif
                        @include('larams::admin.structure.elements.childs_table', ['childs' => $extraChilds, 'sorting' => !empty( $typeConfiguration['child_item_sorting'] ), 'extra_columns' => $typeConfiguration['child_extra_columns'] ] )
                    @endif

                    @if ( count( $extraTypes ) )
                        @include('larams::admin.structure.elements.childs_form', [ 'types' => $extraTypes, 'tree' => 0, 'title' => !empty( $typeConfiguration['child_item_create_title'] ) ? $typeConfiguration['child_item_create_title'] : trans('admin.button.add_new_item') ] )
                    @endif
                </div>
            @endif

            @include('larams::admin.structure.elements.block_before_fields')

            <div id="edit-item" class="well">
                @if ($currentItem->level > 3 || $isDeveloper)
                    <form class="form" action="{{url('admin/structure/save/' . $currentItem->id )}}" enctype="multipart/form-data" method="post" name="doing_stuff_with_content">
                        <fieldset>
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            {!! BootstrapForm::input( ['name' => 'name', 'value' => $currentItem->name, 'title' => trans('admin.field.title') ] ) !!}
                            {!! BootstrapForm::input( ['name' => '', 'readonly' => 'readonly', 'disabled' => 'disabled', 'value' => $currentItem->uri, 'title' => trans('admin.field.link') ] ) !!}

                            @if (config('larams.admin.allow_custom_uri'))
                                {!! BootstrapForm::input( ['name' => 'custom_uri', 'value' => $currentItem->custom_uri, 'title' => trans('admin.field.link') ] ) !!}
                            @endif

                            @if ( !empty( $isDeveloper ) )
                                {!! BootstrapForm::select( ['name' => 'type_id', 'value' => $currentItem->type_id, 'title' => trans('admin.field.type'), 'values' => $types, 'option_key' => 'id', 'option_value' => 'name_lang' ]) !!}
                            @endif
                            @if ( !empty( $typeConfiguration['properties'] ) )
                                @foreach ( $typeConfiguration['properties'] as $property )
                                    <div class="form-group">
                                        <label class="control-label" for="{{$property['name']}}">
                                            @if (!empty( $property['title'] ) ) {{ $property['title'] }}
                                            @elseif ( $property['name'] == 'date') {{trans('admin.field.date')}}
                                            @elseif ( $property['name'] == 'text') {{trans('admin.field.text')}}
                                            @elseif ( $property['name'] == 'image') {{trans('admin.field.image')}}
                                            @else {{trans('admin.field.'. $property['name'] )}}
                                            @endif
                                        </label>
                                        <div class="">
                                            {!! $property['html'] !!}
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">{{trans('admin.button.save')}}</button>
                            </div>
                        </fieldset>
                    </form>
                @endif
            </div>

            @include('larams::admin.structure.elements.block_after_fields')

        </div>
    </div>
</div>

