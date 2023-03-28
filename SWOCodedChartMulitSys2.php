<?php
include_once("../log/logfile_func.inc.php");
if(empty($db)){$db = verbinde_PDOMy();}

$query = "
	select
		sum(case when t.FINANCIAL_CLASSIFICATION_CODE = 'Warranty' and t.call_id is not null and t.CodedBy!='Machine' then 1 else 0 end) as codedIW,
		sum(case when (t.FINANCIAL_CLASSIFICATION_CODE IS NULL or t.FINANCIAL_CLASSIFICATION_CODE != 'Warranty')  and t.call_id is not null and t.CodedBy!='Machine' then 1 else 0 end) as codedOOW,
		t.period
	from
		(
		SELECT
			date_format(c.CALLCLOSEDATE,'%Y-%m') as 'period',
			c.FINANCIAL_CLASSIFICATION_CODE,
			s2i.Call_ID,
			s2i.CodedBy
		from
			qlik_csa.callanalysis_calls c
		left join
			warranty.dwa_swo2issue s2i
		ON
			s2i.Call_Id = c.CALLID
		JOIN
			warranty.dwa_product2kmat p2m
		ON
			p2m.KMAT = c.SYSCODE
		join masterdata.calltypes_notification ctn on ctn.notificationcalltype = c.reason and ctn.category = 'CorrectiveMaintenance'
		where
			p2m.product = '".$_GET["product"]."' 
			and c.CALLCLOSEDATE between '".$_GET["startjahr"]."-".$_GET["StartM"]."-01' and '".$_GET["endjahr"]."-".$_GET["EndM"]."-31'
		group by
			c.callid
	) t
	group by t.period";

$result = $db->query($query) or die('Query failed: ' . $query);

$rows = $result->fetchAll(PDO::FETCH_ASSOC);
$firstrow = 1;
$ray = "[";
foreach($rows as $row) {
	if ($firstrow == 1) {
	$ray .= "[new Date('".substr($row["period"],0,4)."/".substr($row["period"],5)."/01"."'),".$row["codedIW"].",".$row["codedOOW"]."]";
	$firstrow = 0;
	} else {
	$ray .= ",[new Date('".substr($row["period"],0,4)."/".substr($row["period"],5)."/01"."'),".$row["codedIW"].",".$row["codedOOW"]."]";}
}
$ray .= "]";

	echo '<script type="text/javascript">';
	echo "dataarray=".$ray.";";
	echo "new Dygraph(";
	echo 'document.getElementById("SWOCodedChartMulitSys"),';
	echo "dataarray,";
	echo " {";
	echo " includeZero: true, showRoller: false, errorBars: false, legend: 'always', stackedGraph: false, legend: 'always',  title: '".$_GET["product"]." SWOs coded', ";
	echo "ylabel: 'No. of coded SWOs', xlabel: 'Date<br>IW=In Warranty OOW=Out Of Warranty' , labels: ['Date','IW','OOW']";
	echo " }";
	echo " ";
	echo " );";
echo "</script>";?>