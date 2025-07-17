@php
    $clA = implode('', [
        base64_decode('QXBw'),        
        base64_decode('XE1vZGVscw=='), 
        base64_decode('XEFkZG9u'), 
    ]);

    $mW  = implode('', [ base64_decode('d2hlcmU=') ]);
    $mE = implode('', [ base64_decode('ZXhpc3Rz') ]);

    $cN = implode('', [ base64_decode('bmFtZQ==') ]);   
    $vN = implode('', [ base64_decode('UnNzIEFkZG9u') ]); 
    $cS = implode('', [ base64_decode('c3RhdHVz') ]);   
    $vS = implode('', [ base64_decode('aW5zdGFsbGVk') ]); 

    $iI = $clA::{$mW}($cN,   $vN)->{$mW}($cS, $vS)->{$mE}();
@endphp

@if($iI)
    <li>
        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
            <i class="fas fa-rss"></i>
            <span class="nav-text">RSS Automation</span>
        </a>
        <ul aria-expanded="false">
            <li>
                <a href="{{ route('rss.report') }}">
                    <span class="nav-text">RSS Feed Report</span>
                </a>
            </li>
            <li>
                <a href="{{ route('rss.create') }}">
                    <span class="nav-text">Add RSS Feed</span>
                </a>
            </li>
        </ul>
    </li>
@endif
