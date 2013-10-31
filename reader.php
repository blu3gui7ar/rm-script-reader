<?php

$handle = fopen($argv[1],"rb");
$header = fread($handle, 8);

$rs = unpack("Vtotal/Vbeats",$header);
$beats = $rs['beats'];
echo "Total time: {$rs['total']}, Beats: {$beats}\n";

for($i = 0; $i < $beats; ++$i){
    $timeline = fread($handle, 12);
    $rs = unpack("Vtime/Vm/Vmark",$timeline);
    //echo "Timeline: {$rs['time']}, {$rs['m']}, {$rs['mark']}\n";
}

$section2 = fread($handle, 6);
$rs = unpack("vsep/Vkeys",$section2);
$sep = '0x'.dechex($rs['sep']);
//echo "Separator : {$sep}\n";

$keys = $rs['keys'];
$time = 0;

$map = array();
for($i = 0; $i < $keys; ++$i){
    $keyline = fread($handle, 11);
    $rs = unpack("vtype/Vtime/Ckey/Vdata",$keyline);
    $time_o = $time;
    $time = $rs['time'];
    $type = $rs['type'];
    $key = $rs['key'];
    $data = $rs['data'];

    if($data > 0x10000000){
        $data = - ((~$data & 0x11111111)  + 1);
    }
    //echo "{$time}(".dechex($time-$time_o)."):\t{$key}\t[{$type}]:{$data}\n";

    $map[$time][$key] = array('type'=>$type, 'data'=>$data);

    switch($type){
    case 0:
        $map[$time][$key]['view'] = '=';
        break;
    case 1:
        $to = $key + $data;
        if($data > 0){
            $map[$time][$key]['view'] = '|>';
            $map[$time][$to] = array('type'=>'fake', 'view' => '>');
        }else{
            $map[$time][$key]['view'] = '<|';
            $map[$time][$to] = array('type'=>'fake', 'view' => '<');
        }
        break;
    case 2:
        $map[$time][$key]['view'] = '^';
        $to = $time + $data;
        $map[$to][$key]['view'] = '-';
        break;
    case 0x62:
        $map[$time][$key]['view'] = '^';
        break;
    case 0x21:
        $to = $key + $data;
        if($data > 0){
            $map[$time][$key]['view'] = '->';
            //$map[$time][$to] = array('type'=>'fake', 'view' => '>');
        }else{
            $map[$time][$key]['view'] = '<-';
            //$map[$time][$to] = array('type'=>'fake', 'view' => '<');
        }
        break;
    case 0x22:
        $map[$time][$key]['view'] = '+';
        break;
    case 0xa2:
        $map[$time][$key]['view'] = '+';
        $to = $time + $data;
        $map[$to][$key]['view'] = '-';
        break;
    case 0xa1:
        $to = $key + $data;
        if($data > 0){
            $map[$time][$key]['view'] = '->';
            $map[$time][$to] = array('type'=>'fake', 'view' => '>');
        }else{
            $map[$time][$key]['view'] = '<-';
            $map[$time][$to] = array('type'=>'fake', 'view' => '<');
        }
        break;
    default:
        echo "Unknown type: $type\n";
    }
}

krsort($map);
foreach($map AS $time => $tks){
    echo $time.":\t";
    for($i = 0; $i < 5; ++$i){
        if(array_key_exists($i, $tks)){
            if(!isset($tks[$i]['view'])){
                var_dump($tks);
            }
            echo $tks[$i]['view'];
        }else{
            echo ' ';
        }
        echo "\t";
    }
    echo "\n";
}

fclose($handle);

?>
