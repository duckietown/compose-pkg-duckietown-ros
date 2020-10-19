<?php

use \system\classes\BlockRenderer;
use \system\packages\ros\ROS;


class DuckietownMsgs_Twist2DStamped extends BlockRenderer {

    static protected $ICON = [
        "class" => "glyphicon",
        "name" => "dashboard"
    ];

    static protected $ARGUMENTS = [
        "ros_hostname" => [
            "name" => "ROSbridge hostname",
            "type" => "text",
            "mandatory" => False,
            "default" => ""
        ],
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
        ],
        "max_value" => [
            "name" => "Maximum value",
            "type" => "numeric",
            "mandatory" => True
        ],
        "allow_negative" => [
            "name" => "Allow negative values",
            "type" => "boolean",
            "mandatory" => True,
            "default" => True
        ],
        "unit" => [
            "name" => "Unit",
            "type" => "text",
            "mandatory" => True
        ],
        "field" => [
            "name" => "Message field to show",
            "type" => "text",
            "mandatory" => True
        ]
    ];

    protected static function render($id, &$args) {
        ?>
        <canvas class="resizable" style="width:100%; padding:6px; padding-bottom:30px"></canvas>

        <table style="width:100%; height:10px; position:relative; top:-30px">
            <tr>
                <td style="width:35%" class="text-center">
                    0.0
                </td>
                <td style="width:30%" class="text-center">
          <span style="position:relative; top:-20px">
            <?php echo $args['unit'] ?>
          </span>
                </td>
                <td style="width:35%" class="text-center">
                    <?php echo sprintf("%.1f", $args['max_value']) ?>
                </td>
            </tr>
        </table>
        <?php
        $ros_hostname = $args['ros_hostname'] ?? null;
        $ros_hostname = ROS::sanitize_hostname($ros_hostname);
        $connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);
        ?>

        <script type="text/javascript">
            $(document).on("<?php echo $connected_evt ?>", function (evt) {
                // Subscribe to the given topic
                let subscriber = new ROSLIB.Topic({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args['topic'] ?>',
                    messageType: 'duckietown_msgs/Twist2DStamped',
                    queue_size: 1,
                    throttle_rate: <?php echo 1000 / $args['fps'] ?>
                });

                let chart_config = {
                    type: 'pie',
                    data: {
                        datasets: [{
                            data: [0.5, 0.0, 0.0, 0.5],
                            backgroundColor: [
                                window.chartColors.white,
                                window.chartColors.green,
                                window.chartColors.green,
                                window.chartColors.white
                            ]
                        }]
                    },
                    options: {
                        cutoutPercentage: 50,
                        rotation: -Math.PI,
                        circumference: Math.PI,
                        tooltips: {
                            enabled: false
                        },
                        maintainAspectRatio: false
                    }
                };
                // create chart obj
                let ctx = $("#<?php echo $id ?> .block_renderer_container canvas")[0].getContext('2d');
                let chart = new Chart(ctx, chart_config);
                window.mission_control_page_blocks_data['<?php echo $id ?>'] = {
                    chart: chart,
                    config: chart_config,
                    allow_negative: <?php echo $args['allow_negative'] ? 'true' : 'false' ?>
                };

                subscriber.subscribe(function (message) {
                    let max_speed = <?php echo $args['max_value'] ?>;
                    let cur_speed = Math.abs(message["<?php echo $args['field'] ?>"]);
                    let speed_sign = Math.sign(cur_speed);
                    let speed_norm = Math.min(cur_speed, max_speed) / max_speed;
                    // get chart
                    let chart_desc = window.mission_control_page_blocks_data['<?php echo $id ?>'];
                    let chart = chart_desc.chart;
                    let config = chart_desc.config;
                    // update values
                    if (chart_desc.allow_negative) {
                        if (speed_sign === -1) {
                            config.data.datasets[0].data[0] = 0.5;
                            config.data.datasets[0].data[1] = 0.0;
                            config.data.datasets[0].data[2] = speed_norm / 2.0;
                            config.data.datasets[0].data[3] = 0.5 - speed_norm / 2.0;
                        } else {
                            config.data.datasets[0].data[0] = 0.5 - speed_norm / 2.0;
                            config.data.datasets[0].data[1] = speed_norm / 2.0;
                            config.data.datasets[0].data[2] = 0.0;
                            config.data.datasets[0].data[3] = 0.5;
                        }
                    } else {
                        config.data.datasets[0].data[0] = 0.0;
                        config.data.datasets[0].data[1] = speed_norm;
                        config.data.datasets[0].data[2] = 0.0;
                        config.data.datasets[0].data[3] = 1.0 - speed_norm;
                    }
                    // refresh chart
                    chart.update();
                });
            });
        </script>
        <?php
    }
}

?>
