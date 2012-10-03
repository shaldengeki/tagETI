<?php

function displayGoogleLineGraph($chartProperties, $linePoints, $lineProperties, $axesProperties, $chartDivID='chart_div') {
	echo "    <script type=\"text/javascript\">
      google.load(\"visualization\", \"1\", {packages:[\"corechart\"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();\n";
	foreach ($axesProperties as $axis) {
		echo "        data.addColumn('".addslashes($axis['type'])."', '".addslashes($axis['title'])."')\n";
	}
	foreach ($lineProperties as $line) {
		if (isset($line['role'])) {
			echo "        data.addColumn({type: 'number', role: '".addslashes($line['role'])."'}, '".addslashes($line['title'])."');\n";
		} else {
			echo "        data.addColumn('number', '".addslashes($line['title'])."');\n";
		}
	}
	echo "data.addRows([\n";
	foreach ($linePoints as $lineArray) {
		echo "['".$lineArray[0]."',".implode(",", array_slice($lineArray, 1))."],\n";
	}
	echo "]);
        var chart = new google.visualization.LineChart(document.getElementById('".escape_output($chartDivID)."'));
        chart.draw(data, {chartArea: {width: '85%', left: '5%', right: '10%', height: '70%', top: '15%', bottom: '15%'},";
	foreach ($chartProperties as $key => $value) {
		echo $key.": '".addslashes($value)."', ";
	}
	echo "});
      }
    </script>\n";
}

function displayGoogleSteppedAreaChart($chartProperties, $areaPoints, $areaProperties, $axesProperties, $chartDivID='chart_div') {
	echo "    <script type=\"text/javascript\">
      google.load(\"visualization\", \"1\", {packages:[\"corechart\"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();\n";
	foreach ($axesProperties as $axis) {
		echo "        data.addColumn('".addslashes($axis['type'])."', '".addslashes($axis['title'])."')\n";
	}
	foreach ($areaProperties as $area) {
		if (isset($area['role'])) {
			echo "        data.addColumn({type: 'number', role: '".addslashes($area['role'])."'}, '".addslashes($area['title'])."');\n";		
		} else {
			echo "        data.addColumn('number', '".addslashes($area['title'])."');\n";
		}
	}
	echo "data.addRows([\n";
	foreach ($areaPoints as $areaArray) {
		echo "['".$areaArray[0]."',".implode(",", array_map(intval, array_slice($areaArray, 1)))."],\n";
	}
	echo "]);
	var chart = new google.visualization.SteppedAreaChart(document.getElementById('".$chartDivID."'));
        chart.draw(data, {chartArea: {width: '75%', left: '5%', right: '20%', height: '80%', top: '10%', bottom: '10%'},";
	foreach ($chartProperties as $key => $value) {
		echo $key.": '".addslashes($value)."', ";
	}
	echo "});
      }
    </script>\n";
}

function displayGoogleBarChart($chartProperties, $barPoints, $barProperties, $axesProperties, $chartDivID='chart_div') {
	echo "    <script type=\"text/javascript\">
      google.load(\"visualization\", \"1\", {packages:[\"corechart\"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();\n";
	foreach ($axesProperties as $axis) {
		echo "        data.addColumn('".addslashes($axis['type'])."', '".addslashes($axis['title'])."')\n";
	}
	foreach ($barProperties as $bar) {
		if (isset($bar['role'])) {
			echo "        data.addColumn({type: 'number', role: '".addslashes($bar['role'])."'}, '".addslashes($bar['title'])."');\n";
		} else {
			echo "        data.addColumn('number', '".addslashes($bar['title'])."');\n";
		}
	}
	echo "data.addRows([\n";
	foreach ($barPoints as $barArray) {
		echo "['".$barArray[0]."',".implode(",", array_slice($barArray, 1))."],\n";
	}
	echo "]);
	var chart = new google.visualization.ColumnChart(document.getElementById('".$chartDivID."'));
        chart.draw(data, {chartArea: {width: '75%', left: '5%', right: '20%', height: '80%', top: '10%', bottom: '10%'},";
	foreach ($chartProperties as $key => $value) {
		echo $key.": '".addslashes($value)."', ";
	}
	echo "});
      }
    </script>\n";
}

function displayGooglePieChart($chartProperties, $categoryData, $axesProperties, $chartDivID='chart_div') {
	echo "    <script type=\"text/javascript\">
      google.load(\"visualization\", \"1\", {packages:[\"corechart\"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();\n";
	foreach ($axesProperties as $axis) {
		echo "        data.addColumn('".$axis['type']."', '".$axis['title']."')\n";
	}
	echo "        data.addRows(".intval(count($categoryData)).");\n";
	$i = 0;
	foreach ($categoryData as $category=>$count) {
		echo "        data.setValue(".$i.", 0, '".htmlentities($category)."');
        data.setValue(".$i.", 1, ".intval($count).");\n";
		$i++;
	}

	echo "        var chart = new google.visualization.PieChart(document.getElementById('".$chartDivID."'));
        chart.draw(data, {chartArea: {width: '80%', left: '5%', right: '5%', height: '80%', top: '10%', bottom: '10%'},";
	foreach ($chartProperties as $key => $value) {
		echo $key.": '".$value."', ";
	}
	echo "});
      }
    </script>\n";
}

function displayGoogleScatterPlot($chartProperties, $point_array, $axesProperties, $chartDivID='chart_div') {
	echo "    <script type=\"text/javascript\">
      google.load(\"visualization\", \"1\", {packages:[\"corechart\"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();\n";
	foreach ($axesProperties as $axis) {
		echo "        data.addColumn('".addslashes($axis['type'])."', '".addslashes($axis['title'])."')\n";
	}
	echo "data.addRows([\n";
	foreach ($point_array as $point) {
		echo "[".$point[0].",".$point[1]."],\n";
	}
	echo "]);
        var chart = new google.visualization.ScatterChart(document.getElementById('".$chartDivID."'));
        chart.draw(data, {chartArea: {width: '85%', left: '8%', right: '10%', height: '70%', top: '15%', bottom: '15%'},";
	foreach ($chartProperties as $key => $value) {
    if (is_array($value)) {
      echo $key.": {";
      $valueStrings = array();
      foreach ($value as $key2 => $value2) {
        $valueStrings[] = $key2.": '".addslashes($value2)."'";
      }
      echo implode(",", $valueStrings)."},";
    } else {
      echo $key.": '".addslashes($value)."', ";
    }
	}
	echo "});
      }
    </script>\n";
}
function displayTagActivityGraph($title, $linePoints, $tags, $divID="activityTimeline", $height=300, $background='#FFFFFF', $xaxis = "Date") {
  /* 
    Given a title, some datapoints, and a set of tags, display a line chart for these points.
  */ 
  $lineChartProperties = array(
    'height' => $height,
    'title' => $title, 
    'backgroundColor' => $background
  );
  $lineChartAxesProperties = array(
    'x' => array(
      'title' => $xaxis,
      'type' => 'string'
    )
  );
  foreach ($tags as $tag) {
    $lineChartAxesProperties[$tag->name] = array(
      'title' => $tag->name,
      'type' => 'number'
    );
  }
  displayGoogleLineGraph($lineChartProperties, $linePoints, array(), $lineChartAxesProperties, $divID);
  echo "    <div id='".escape_output($divID)."'></div>\n";
}
?>