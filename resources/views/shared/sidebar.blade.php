<style>
    #sidebar-menu ul li a{
        font-size: 0.8rem !important;
    }
</style>

<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">

            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" data-key="t-menu">Menu</li>
                {!! getUserMenu() !!}
            </ul>
        </div>
    </div>
</div>
