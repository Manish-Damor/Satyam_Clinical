<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Google Charts</title>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    padding: 16px;
    font-family: Arial, sans-serif;
    background: #f7f9fb;
  }
  .charts-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(280px, 1fr));
    gap: 16px;
    max-width: 1200px;
    margin: 0 auto;
  }
  .chart-card {
    background: #fff;
    border: 1px solid #dbe4ee;
    border-radius: 8px;
    padding: 10px;
    min-height: 340px;
  }
  .chart-canvas {
    width: 100%;
    height: 320px;
  }
  @media (max-width: 767px) {
    body {
      padding: 10px;
    }
    .charts-grid {
      grid-template-columns: 1fr;
    }
    .chart-card {
      min-height: 280px;
    }
    .chart-canvas {
      height: 260px;
    }
  }
</style>
</head>
<body>
<div class="charts-grid">
  <div class="chart-card">
    <div id="myChart" class="chart-canvas"></div>
  </div>
  <div class="chart-card">
    <div id="myChart1" class="chart-canvas"></div>
  </div>
</div>
<?php
error_reporting(0);
$a=array("Italy","France","Spain","USA","Argentina");
$b=array(50,55,60,70,80);
$cnt=count($a);
$data='';
for ($i=0;$i<$cnt;$i++){
  $data.="['".$a[$i]."',".$b[$i]."],";
}
echo $data; //used this $data variable in js
?>
<script>
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);

function drawChart() {
var data = google.visualization.arrayToDataTable([ ['Contry', 'Mhl'],<?php echo $data;?>]);

var options = {
  title:'World Wide Wine Production',
  is3D:true,
  chartArea: { width: '85%', height: '75%' }
};

var chart = new google.visualization.PieChart(document.getElementById('myChart'));
  chart.draw(data, options);

  var chart = new google.visualization.BarChart(document.getElementById('myChart1'));
  chart.draw(data, options);
}
</script>

</body>
</html>

