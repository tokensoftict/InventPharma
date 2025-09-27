<div x-data="scanData" x-init="initScanner">
   <div class="row">
        <div class="col-12" style="position: relative">
            <div wire:loading wire:target="checkoutInvoice">
                <span  class="spinner-border spinner-border-sm me-2" role="status"></span>
            </div>
            <div style="width: 100%;  padding: 0px;background: #f2f2f2;" id="reader" wire:loading.remove wire:target="checkoutInvoice"></div>
        </div>
   </div>

    <script>

        function scanData()
        {
            return {

                initScanner(){
                    let  config = {
                        fps: 10,
                        qrbox: 250,

                        showTorchButtonIfSupported : true,
                        rememberLastUsedCamera: true
                    }

                    function onScanSuccess(decodedText, decodedResult){
                        //decodedText
                        beep();
                        @this.set('invoice_number', decodedText, true);
                        @this.checkoutInvoice().then(function (response) {
                            beep();
                        });
                        html5QrcodeScanner.clear();
                    }

                    function onScanError(errorMessage) {
                        console.log(errorMessage);
                    }

                    let html5QrcodeScanner = new Html5QrcodeScanner(
                        "reader", config);
                    html5QrcodeScanner.render(onScanSuccess);
                    let ping = setInterval(function(){
                        $('#reader button')
                            .addClass('btn')
                            .addClass('btn-lg')
                            .addClass('btn-primary')
                            .addClass('mb-2');

                        $('#reader select').addClass('form-control').addClass('mt-2').addClass('mb-2')
                    },10);
                }

            };
        }


        const myAudioContext = new AudioContext();

        /**
         * Helper function to emit a beep sound in the browser using the Web Audio API.
         *
         * @param {number} duration - The duration of the beep sound in milliseconds.
         * @param {number} frequency - The frequency of the beep sound.
         * @param {number} volume - The volume of the beep sound.
         *
         * @returns {Promise} - A promise that resolves when the beep sound is finished.
         */
        function beep(duration, frequency, volume){
            return new Promise((resolve, reject) => {
                // Set default duration if not provided
                duration = duration || 200;
                frequency = frequency || 440;
                volume = volume || 100;

                try{
                    let oscillatorNode = myAudioContext.createOscillator();
                    let gainNode = myAudioContext.createGain();
                    oscillatorNode.connect(gainNode);

                    // Set the oscillator frequency in hertz
                    oscillatorNode.frequency.value = frequency;

                    // Set the type of oscillator
                    oscillatorNode.type= "square";
                    gainNode.connect(myAudioContext.destination);

                    // Set the gain to the volume
                    gainNode.gain.value = volume * 0.01;

                    // Start audio with the desired duration
                    oscillatorNode.start(myAudioContext.currentTime);
                    oscillatorNode.stop(myAudioContext.currentTime + duration * 0.001);

                    // Resolve the promise when the sound is finished
                    oscillatorNode.onended = () => {
                        resolve();
                    };
                }catch(error){
                    reject(error);
                }
            });
        }

    </script>
</div>
