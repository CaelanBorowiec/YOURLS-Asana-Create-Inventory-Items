$(document).ready(function(){
    // Convert all the links with the progress-button class to
    // actual buttons with progress meters.
    // You need to call this function once the page is loaded.
    // If you add buttons later, you will need to call the function only for them.
    $('.progress-button').progressInitialize();

    var csvString = "";
    $('#generateButton').one('click', StartGenerateButton);

  function StartGenerateButton(e){
      e.preventDefault();
      var button = $(this);

      button.progressSet(5); // Show some immediate user feedback
      button.attr("data-finished", "Download CSV");
      button.removeClass('failure');
      button.addClass('success');

      var start = parseInt($("#start").val(), 10); // Make sure this is an int or we'll just be appending numbers
      var num = parseInt($("#count").val(), 10);
      var prefix = $("#prefix").val();

      if (isNaN(start) || start <= 1)
      {
        printError("Please enter a starting barcode number.");
        throw new Error("Please enter a starting barcode number.");
      }
      if (isNaN(num) || num <= 1)
      {
        num = 1;
        printError("No count entered, assuming 1.");
      }

      var i = 0; // Loop
      var progress = 0; // Progress percentage
      while (i < num)
      {
        var code = start+i; // The shortURl
        // Call the YOURLS API defined in plugin.php.
        // Note, we're using count=1 so that the API returns after every creation, which allows the button to update more often.
        var yourlsAPI = "/yourls-api.php?action=createaot&start="+code+"&count=1";
        if (prefix != "")
          yourlsAPI += "&prefix="+prefix;

        $.get( yourlsAPI, function( data ) {
          //console.log(data);

          //Error checking should be added here

          var xmlResults = $(data).find("results").text(); // This should be JSON inside XML
          var jsonText = $('<textarea/>').html(xmlResults).text(); // Fix encoding
          var jsonResults = JSON.parse(jsonText); // Create a json object from the string
          var keys = Object.keys(jsonResults);  // We only really care about the shortURL 'key'

          $.each(keys, function( index, value ) {
            //console.log( index + ": " + value ); // Prints the array position : the key (shorturl)
            //console.log("-> " + jsonResults[value]); // Prints the key value (asana id or error)
            if ($.isNumeric(jsonResults[value]))
            {
              csvString += keys[index] + "," + window.location.hostname+"/"+keys[index] + "\n";
            }
          });

          progress += (100/num);
          button.progressSet(progress);
          if (progress >= 100)
          {
            $('#generateButton').on('click', function(e)
            {
              //console.log(csvString); // Print the csv we built with all the queries
              download(""+ new Date()+".csv", csvString);
            });
          }
        }).fail(function(data)
        {
          // data.responseText has the info YOURLS returned
          //console.log(data.responseText);
          var statusCode = $(data.responseText).find("statusCode").text();

          //Because we're hitting the backend with single requests, a failed code will always exit with '400'
          //We can check num==1 to see if it is the only request.
          if (num == 1 && statusCode == 400)  // Hard failure
          {
            printError("Failed to run: " + $(data.responseText).find("message").text());
            //throw new Error("Failed to run: " + $(data.responseText).find("message").text());
            button.attr("data-finished", "Failed. Try again?");
            button.removeClass('success');
            button.addClass('failure');
            button.progressSet(100);

            button.one('click', StartGenerateButton);
            i = num; // Should be "break"
          }
          else
          {
            progress += (100/num);
            button.progressSet(progress);
            var errorMessage = $(data.responseText).find("message").text();
            errorMessage = errorMessage.replace("Start v", "V");  // Don't say "Start value"
            printError(errorMessage);
          }
        });
        i++;
      }

  }

    // Custom progress handling

    var controlButton = $('#controlButton');

    controlButton.click(function(e){
        e.preventDefault();

        // You can optionally call the progressStart function.
        // It will simulate activity every 2 seconds if the
        // progress meter has not been incremented.

        controlButton.progressStart();
    });

    $('.command.increment').click(function(){

        // Increment the progress bar with 10%. Pass a number
        // as an argument to increment with a different amount.

        controlButton.progressIncrement();
    });

    $('.command.set-to-1').click(function(){

        // Set the progress meter to the specified percentage

        controlButton.progressSet(1);
    });

    $('.command.set-to-50').click(function(){
        controlButton.progressSet(50);
    });

    $('.command.finish').click(function(){

        // Set the progress meter to 100% and show the done text.
        controlButton.progressFinish();
    });

});

// The progress meter functionality is available as a series of plugins.
// You can put this code in a separate file if you wish to keep things tidy.

(function($){

    // Creating a number of jQuery plugins that you can use to
    // initialize and control the progress meters.

    $.fn.progressInitialize = function(){

        // This function creates the necessary markup for the progress meter
        // and sets up a few event listeners.

        // Loop through all the buttons:

        return this.each(function(){

            var button = $(this),
                progress = 0;

            // Extract the data attributes into the options object.
            // If they are missing, they will receive default values.

            var options = $.extend({
                type:'background-horizontal',
                loading: 'Loading..',
                finished: 'Done!'
            }, button.data());

            // Add the data attributes if they are missing from the element.
            // They are used by our CSS code to show the messages
            button.attr({'data-loading': options.loading, 'data-finished': options.finished});

            // Add the needed markup for the progress bar to the button
            var bar = $('<span class="tz-bar ' + options.type + '">').appendTo(button);

            // The progress event tells the button to update the progress bar
            button.on('progress', function(e, val, absolute, finish){

                if(!button.hasClass('in-progress')){

                    // This is the first progress event for the button (or the
                    // first after it has finished in a previous run). Re-initialize
                    // the progress and remove some classes that may be left.

                    bar.show();
                    progress = 0;
                    button.removeClass('finished').addClass('in-progress')
                }

                // val, absolute and finish are event data passed by the progressIncrement
                // and progressSet methods that you can see near the end of this file.

                if(absolute){
                    progress = val;
                }
                else{
                    progress += val;
                }

                if(progress >= 100){
                    progress = 100;
                }

                if(finish){

                    button.removeClass('in-progress').addClass('finished');

                    bar.delay(500).fadeOut(function(){

                        // Trigger the custom progress-finish event
                        button.trigger('progress-finish');
                        setProgress(0);
                    });

                }

                setProgress(progress);
            });

            function setProgress(percentage){
                bar.filter('.background-horizontal,.background-bar').width(percentage+'%');
                bar.filter('.background-vertical').height(percentage+'%');
            }

        });

    };

    // progressStart simulates activity on the progress meter. Call it first,
    // if the progress is going to take a long time to finish.

    $.fn.progressStart = function(){

        var button = this.first(),
            last_progress = new Date().getTime();

        if(button.hasClass('in-progress')){
            // Don't start it a second time!
            return this;
        }

        button.on('progress', function(){
            last_progress = new Date().getTime();
        });

        // Every half a second check whether the progress
        // has been incremented in the last two seconds

        var interval = window.setInterval(function(){

            if( new Date().getTime() > 2000+last_progress){

                // There has been no activity for two seconds. Increment the progress
                // bar a little bit to show that something is happening

                button.progressIncrement(5);
            }

        }, 500);

        button.on('progress-finish',function(){
            window.clearInterval(interval);
        });

        return button.progressIncrement(10);
    };

    $.fn.progressFinish = function(){
        return this.first().progressSet(100);
    };

    $.fn.progressIncrement = function(val){

        val = val || 10;

        var button = this.first();

        button.trigger('progress',[val])

        return this;
    };

    $.fn.progressSet = function(val){
        val = val || 10;

        var finish = false;
        if(val >= 100){
            finish = true;
        }

        return this.first().trigger('progress',[val, true, finish]);
    };

    // This function creates a progress meter that
    // finishes in a specified amount of time.

    $.fn.progressTimed = function(seconds, cb){

        var button = this.first(),
            bar = button.find('.tz-bar');

        if(button.is('.in-progress')){
            return this;
        }

        // Set a transition declaration for the duration of the meter.
        // CSS will do the job of animating the progress bar for us.

        bar.css('transition', seconds+'s linear');
        button.progressSet(99);

        window.setTimeout(function(){
            bar.css('transition','');
            button.progressFinish();

            if($.isFunction(cb)){
                cb();
            }

        }, seconds*1000);
    };

})(jQuery);

function printError(error)
{
  $('#errors').val($('#errors').val() + "\n" + error);
}

function download(filename, text) {
  var element = document.createElement('a');
  element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
  element.setAttribute('download', filename);

  element.style.display = 'none';
  document.body.appendChild(element);

  element.click();

  document.body.removeChild(element);
}
