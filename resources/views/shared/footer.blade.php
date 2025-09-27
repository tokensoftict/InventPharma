<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                {{ date('Y') }}
            </div>
            <div class="col-sm-6">
                <div class="text-sm-end d-none d-sm-block">
                    Design & Develop by <a href="#!" class="text-decoration-underline">Tokensoft ICT 08130610626</a>
                </div>
            </div>
        </div>
    </div>
</footer>
<script>
    function formatMoney(amount, decimalCount = 2, decimal = ".", thousands = ",") {
        try {
            decimalCount = Math.abs(decimalCount);
            decimalCount = isNaN(decimalCount) ? 2 : decimalCount;

            const negativeSign = amount < 0 ? "-" : "";

            let i = parseInt(amount = Math.abs(Number(amount) || 0).toFixed(decimalCount)).toString();
            let j = (i.length > 3) ? i.length % 3 : 0;

            return negativeSign + (j ? i.substr(0, j) + thousands : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands) + (decimalCount ? decimal + Math.abs(amount - i).toFixed(decimalCount).slice(2) : "");
        } catch (e) {
            console.log(e)
        }
    }

    window.addEventListener('load', function(){
        $(document).ready(function () {
            $('.print').on('click', function (e) {
                e.preventDefault();
                var href = $(this).attr('href');
                var win = window.open(href, "MsgWindow", "width=800,height=500");
                window.location.reload();
                win.onload = function () {
                    win.print();
                }
                return false;
            });

            $('.confirm_action').on("click",function(e){
                if(confirm($(this).attr('data-msg') )== false){
                    e.preventDefault();
                }
            });
        });
    });


    function confirm_action(elem){
        if(confirm($(elem).attr('data-msg')) == true){
            return true;
        }
        return false;
    }

    function open_print_window(elem){
        var href = $(elem).attr('href');
        var win = window.open(href, "MsgWindow", "width=800,height=500");
        window.location.reload();
        win.onload = function(){
            win.print();
        }
        return false;
    }


</script>
