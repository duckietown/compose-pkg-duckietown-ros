<?php
use \system\classes\BlockRenderer;
use \system\packages\ros\ROS;

class DuckietownMsgs_AprilTagsWithInfos extends BlockRenderer{

  static protected $ICON = [
    "class" => "fa",
    "name" => "map-marker"
  ];

  static protected $ARGUMENTS = [
    "topic" => [
      "name" => "ROS Topic",
      "type" => "text",
      "mandatory" => True
    ],
    "fps" => [
      "name" => "Update frequency (Hz)",
      "type" => "numeric",
      "mandatory" => True,
      "default" => 5
    ]
  ];

  protected static function render( $id, &$args ){
    ?>
    <h3 id="street_name" class="text-center" style="margin:auto"></h3>

    <script type="text/javascript">
    $( document ).on("<?php echo ROS::$ROSBRIDGE_CONNECTED ?>", function(evt){
      // Subscribe to the given topic
      subscriber = new ROSLIB.Topic({
        ros : window.ros,
        name : '<?php echo $args['topic'] ?>',
        messageType : 'duckietown_msgs/AprilTagsWithInfos',
        queue_size : 1,
        throttle_rate : <?php echo 1000/$args['fps'] ?>
      });

      subscriber.subscribe(function(message) {
        // get road name
        $.each(message.infos, function(i) {
          tag = message.infos[i];
          if( tag.tag_type == 0 ){
            // street name
            street_name = $('#<?php echo $id ?> .block_renderer_container #street_name');
            street_name.html( message.infos[i].street_name );
          }
        });
      });
    });
    </script>
    <?php
  }
}
?>
