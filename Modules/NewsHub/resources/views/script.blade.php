@if (empty($cfg['ok']))
@else
(function(){
"use strict";

var APSH_CFG = {!! json_encode($cfg, JSON_UNESCAPED_SLASHES) !!};

function crop(s, n){ 
  s = (s||"").toString(); 
  var max = n||80; 
  return s.length <= max ? s : s.slice(0, max) + "…"; 
}

function apluselfhost(cfg){
  try{
    if(!cfg||!cfg.ok) return;

    // ---------- Common ----------
    var D=document,W=window,B=D.body, site=cfg.site;
    var isMobile = function(){ return (W.innerWidth||0) <= 768; };
    var allowDesktop = function(opt){ return !!(opt.show_on_desktop ?? opt.enable_desktop); };
    var allowMobile  = function(opt){ return !!(opt.show_on_mobile  ?? opt.enable_mobile); };

    function esc(s){return (s||"").toString().replace(/[&<>"']/g,function(m){return({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"})[m]})}
    function h(tag,props,children){
      var el=D.createElement(tag);
      if(props){ for(var k in props){
        if(k==="style" && typeof props[k]==="object"){ Object.assign(el.style, props[k]); }
        else if(k==="class"){ el.className=props[k]; }
        else if(k==="html"){ el.innerHTML=props[k]; }
        else if(k.startsWith("on") && typeof props[k]==="function"){ el.addEventListener(k.slice(2),props[k]); }
        else { el.setAttribute(k, props[k]); }
      }}
      if(children){ (Array.isArray(children)?children:[children]).forEach(function(c){
        if(c==null) return; if(typeof c==="string") el.appendChild(D.createTextNode(c)); else el.appendChild(c);
      });}
      return el;
    }
    function once(fn){ var called=false; return function(){ if(!called){ called=true; return fn.apply(this,arguments);} }; }
    function shuffle(arr){ for(let i=arr.length-1;i>0;i--){ const j=Math.floor(Math.random()*(i+1)); [arr[i],arr[j]]=[arr[j],arr[i]]; } return arr; }
    function limit(arr,n){ return Array.isArray(arr)?arr.slice(0, n):[]; }
    function crop(s, n){
      s = (s||"").toString();
      var max = n||80;
      return s.length <= max ? s : s.slice(0, max) + "…";
    }

    // Normalize feed URL relative to current page origin if needed
    function toAbsUrl(u){
      if(!u) return "";
      try{
        if(/^https?:\/\/?/i.test(u)) return u;
        if(u.startsWith("//")) return location.protocol + u;
        if(u.startsWith("/"))  return location.origin + u;
        var a=D.createElement("a"); a.href=u; return a.href;
      }catch(_){ return u; }
    }

    // ----- RSS/Atom fetch + parse in-browser -----
    function fetchFeed(url){
      var abs = toAbsUrl(url);
      var ctrl = new AbortController(); setTimeout(function(){ctrl.abort()}, 12000);
      return fetch(abs, {signal:ctrl.signal, credentials:"omit"})
        .then(function(r){ return r.text(); })
        .then(function(xmlText){ return parseFeed(xmlText, abs); })
        .catch(function(){ return []; });
    }

    function textContent(node, sel){
      var t = node.querySelector(sel); return t ? (t.textContent||"").trim() : "";
    }
    function attr(node, sel, name){
      var t = node.querySelector(sel); return t ? (t.getAttribute(name)||"").trim() : "";
    }

    function parseFeed(xmlText){
      try{
        var p = new DOMParser();
        var doc = p.parseFromString(xmlText, "application/xml");
        // RSS <item>
        var items = Array.from(doc.querySelectorAll("item")).map(function(it){
          var title = textContent(it,"title");
          var link  = textContent(it,"link");
          var desc  = textContent(it,"description") || textContent(it,"content\\:encoded");
          var date  = textContent(it,"pubDate");
          var img   = attr(it,"enclosure[url]","url") || attr(it,"media\\:content","url") || attr(it,"media\\:thumbnail","url");
          if(!img){ img = (desc.match(/<img[^>]+src=["']([^"']+)["']/i)||[])[1] || ""; }
          var cats = extractCategories(it);
          return normItem(title, link, img, desc, date, cats);
        });

        if(!items.length){
          // Atom <entry>
          items = Array.from(doc.querySelectorAll("entry")).map(function(en){
            var title = textContent(en,"title");
            var linkEl = en.querySelector("link[href]");
            var link  = linkEl ? (linkEl.getAttribute("href")||"") : "";
            var desc  = textContent(en,"summary") || textContent(en,"content");
            var date  = textContent(en,"updated") || textContent(en,"published");
            var img   = (desc.match(/<img[^>]+src=["']([^"']+)["']/i)||[])[1] || "";
            var cats = extractCategories(en);
            return normItem(title, link, img, desc, date, cats);
          });
        }

        items.sort(function(a,b){ return (b.date||0)-(a.date||0); });
        return items;
      }catch(_){ return []; }
    }

    function normItem(title, link, image, description, date, categories){
      var d=0; try{ d= date ? (new Date(date)).getTime() : 0; }catch(_){}
      image = image ? toAbsUrl(image) : "";
      link  = link  ? toAbsUrl(link) : "";
      return { title:title||"Untitled", link:link, image:image, description:description||"", date:d,
      categories: Array.isArray(categories) ? categories : [] };
    }

    // ---------- Styles (single injection) ----------
    if(!D.getElementById("aplu-push-self-host-style")){
      var css = `
/* scoped base */
.aplu-push-self-host-apsh-wrap{z-index:2147483646}
.aplu-push-self-host-apsh-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.18)}
.aplu-push-self-host-apsh-header{color:#fff;padding:10px 12px;display:flex;align-items:center;justify-content:space-between}
.aplu-push-self-host-apsh-close{cursor:pointer;background:none;border:none;color:#fff;font-size:18px;line-height:1;padding:0 4px}
.aplu-push-self-host-apsh-body{padding:12px;overflow:auto;max-height: 320px;}
.aplu-push-self-host-apsh-body-flask{padding:12px;overflow:auto;}
.aplu-push-self-host-apsh-row{display:flex;gap:8px;align-items:flex-start;border-bottom:1px dashed #b4b4b4;padding:6px 0;font-size:12px;align-items: center;}
.aplu-push-self-host-apsh-row:last-child{border-bottom:none;justify-content: center;}
.aplu-push-self-host-apsh-thumb{width:100px;height:65px;border-radius:6px;object-fit:cover;background:#f3f3f3}
.aplu-push-self-host-apsh-item-link{font-weight:600;text-decoration:none;color:#222;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;text-overflow: ellipsis;margin-bottom:2px;}
.aplu-push-self-host-apsh-item-link:hover{text-decoration:underline}

/* roll button & panel */
@keyframes bounce {
  0%,20%,50%,80%,100%{transform:translateY(0)}
  40%{transform:translateY(-10px)}
  60%{transform:translateY(-5px)}
}
.aplu-push-self-host-apsh-roll-wrap{position:fixed;bottom:50px;right:20px;display:flex;flex-direction:column;gap:10px;align-items:flex-end;animation: bounce 2s infinite;}
.aplu-push-self-host-apsh-roll-left{right:auto;left:20px;align-items:flex-start}
.aplu-push-self-host-apsh-roll-btn{background:#fd683e;color:#fff;width:45px;height:45px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(0,0,0,.22);cursor:pointer;font-size:20px;user-select:none}
.aplu-push-self-host-apsh-roll-card{width:350px;display:none}

/* flask modal */
.aplu-push-self-host-apsh-flask-mask{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;align-items:center;justify-content:center}
.aplu-push-self-host-apsh-flask{width:min(720px,94%);max-height:90vh}
.aplu-push-self-host-apsh-grid{display:flex;flex-wrap:wrap;gap:10px}
.aplu-push-self-host-apsh-grid-item{background:#fff;border-radius:10px;border:1px solid #e3e3e3;text-align:center;width:calc(50% - 10px);box-sizing:border-box;padding:8px;line-height:1.3;cursor:pointer;}
.aplu-push-self-host-apsh-grid-item img{width:100%;height:140px;object-fit:cover;border-radius:6px;margin-bottom:8px}
.aplu-push-self-host-apsh-grid-item h3{margin:5px 0;font-size:13px;line-height:1.3;word-break:break-word;font-weight:700}
.aplu-push-self-host-apsh-grid-item p{font-size:13px;line-height:1.5;margin:0px;}
@media (max-width:576px){ .aplu-push-self-host-apsh-grid-item img{height:90px} .aplu-push-self-host-apsh-grid-item{width:100%} .aplu-push-self-host-apsh-body-flask{overflow: scroll !important;max-height: 425px;} .aplu-push-self-host-apsh-roll-card{width: 95%;} }

/* bottom slider */
.aplu-push-self-host-apsh-slider{position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:2px solid #000;box-shadow:0 -8px 24px rgba(0,0,0,.15);padding:10px 34px;display:none}
.aplu-push-self-host-apsh-slider-track{display:flex;gap:8px;overflow:hidden;scroll-snap-type:x mandatory}
.aplu-push-self-host-apsh-slider-item{min-width:355px;max-width:355px;scroll-snap-align:center;background:#F9F9F9;border:1px solid #ddd;border-radius:6px;padding:6px;display:flex;gap:10px;position:relative}
.aplu-push-self-host-apsh-slider-item img{width:110px;height:70px;object-fit:cover;border-radius:5px}
.aplu-push-self-host-apsh-slider-text a{font-family:sans-serif;font-size:14px;font-weight:600;text-decoration:none;color:#222;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 3;overflow: hidden;text-overflow: ellipsis;}
.aplu-push-self-host-apsh-slider-x{position:absolute;top:4px;right:8px;font-size:20px;cursor:pointer;color:#666}
.aplu-push-self-host-apsh-arrow{font-family:sans-serif;position:absolute;top:50%;transform:translateY(-50%);width:26px;height:26px;border-radius:6px;border:1px solid currentColor;background:currentColor;color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;padding:10px;}
.aplu-push-self-host-apsh-prev{left:6px}
.aplu-push-self-host-apsh-next{right:6px}
.aplu-push-self-host-apsh-cat-badge{font-family:sans-serif;position:absolute; right:0px; bottom:0px;padding:4px 8px; border-bottom-right-radius:5px;font-size:10px; line-height:1; font-weight:700; color:#fff;user-select:none; pointer-events:none;}
`;
      var st=h("style",{id:"aplu-push-self-host-style"},css); D.head.appendChild(st);
    }

    function getIconSvg(icon){
      switch(icon){
        case "fa fa-bell":
          return `<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M12 1c3.681 0 7 2.565 7 6v4.539c0 .642.189 1.269.545 1.803l2.2 3.298A1.517 1.517 0 0 1 20.482 19H15.5a3.5 3.5 0 1 1-7 0H3.519a1.518 1.518 0 0 1-1.265-2.359l2.2-3.299A3.25 3.25 0 0 0 5 11.539V7c0-3.435 3.318-6 7-6ZM6.5 7v4.539a4.75 4.75 0 0 1-.797 2.635l-2.2 3.298-.003.01.001.007.004.006.006.004.007.001h16.964l.007-.001.006-.004.004-.006.001-.006a.017.017 0 0 0-.003-.01l-2.199-3.299a4.753 4.753 0 0 1-.798-2.635V7c0-2.364-2.383-4.5-5.5-4.5S6.5 4.636 6.5 7ZM14 19h-4a2 2 0 1 0 4 0Z"></path></svg>`;
        case "fa fa-bullhorn":
          return `<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><g id="Bullhorn"><path d="M4.5,16.474c-0.849,-0.4 -1.436,-1.263 -1.436,-2.264l-0,-4.419c-0,-1.38 1.118,-2.5 2.5,-2.5l4.343,0c0.793,0 1.581,-0.132 2.33,-0.392c1.859,-0.705 3.792,-1.727 5.24,-2.922l0.869,-0.718c0.015,-0.013 0.032,-0.025 0.049,-0.036c0.666,-0.424 1.538,0.054 1.538,0.844l0,6.717c0.572,0.11 1.004,0.613 1.004,1.217c-0,0.604 -0.432,1.106 -1.004,1.216l-0,6.718c-0,0.787 -0.872,1.267 -1.538,0.843c-0.017,-0.011 -0.034,-0.023 -0.05,-0.036l-0.868,-0.718c-1.446,-1.195 -3.364,-2.214 -5.226,-2.891c-0.748,-0.261 -1.536,-0.394 -2.328,-0.394c-0.609,-0.029 -1.265,-0.029 -1.265,-0.029l0,2.147c0,1.148 -0.931,2.079 -2.079,2.079c-1.148,-0 -2.079,-0.931 -2.079,-2.079l0,-2.383Zm1,0.236l0,2.147c0,0.596 0.483,1.079 1.079,1.079c0.596,-0 1.079,-0.483 1.079,-1.079c0,-0 0,-2.147 0,-2.147l-2.094,-0c-0.031,-0 -0.053,-0 -0.064,-0Zm6,-0.882l0.142,0.04c2.37,0.664 4.575,1.817 6.473,3.385l0.818,0.677l-0,-15.859l-0.82,0.677c-1.897,1.566 -4.1,2.717 -6.468,3.379l-0.145,0.041l-0,7.66Zm-2.842,-0.118l1.842,0l-0,-7.419l-4.936,0c-0.829,0 -1.5,0.672 -1.5,1.5l-0,4.419c-0,0.829 0.671,1.5 1.499,1.5l3.095,0Z"></path></g></svg>`;
        case "fa fa-newspaper":
          return `<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path fill="none" stroke-linejoin="round" stroke-width="32" d="M368 415.86V72a24.07 24.07 0 0 0-24-24H72a24.07 24.07 0 0 0-24 24v352a40.12 40.12 0 0 0 40 40h328"></path><path fill="none" stroke-linejoin="round" stroke-width="32" d="M416 464a48 48 0 0 1-48-48V128h72a24 24 0 0 1 24 24v264a48 48 0 0 1-48 48z"></path><path fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M240 128h64m-64 64h64m-192 64h192m-192 64h192m-192 64h192"></path><path d="M176 208h-64a16 16 0 0 1-16-16v-64a16 16 0 0 1 16-16h64a16 16 0 0 1 16 16v64a16 16 0 0 1-16 16z"></path></svg>`;
        case "fa fa-star":
          return `<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><g id="Star"><path d="M16.6,20.463a1.5,1.5,0,0,1-.7-.174l-3.666-1.927a.5.5,0,0,0-.464,0L8.1,20.289a1.5,1.5,0,0,1-2.177-1.581l.7-4.082a.5.5,0,0,0-.143-.442L3.516,11.293a1.5,1.5,0,0,1,.832-2.559l4.1-.6a.5.5,0,0,0,.376-.273l1.833-3.714a1.5,1.5,0,0,1,2.69,0l1.833,3.714a.5.5,0,0,0,.376.274l4.1.6a1.5,1.5,0,0,1,.832,2.559l-2.965,2.891a.5.5,0,0,0-.144.442l.7,4.082A1.5,1.5,0,0,1,16.6,20.463Zm-3.9-2.986L16.364,19.4a.5.5,0,0,0,.725-.527l-.7-4.082a1.5,1.5,0,0,1,.432-1.328l2.965-2.89a.5.5,0,0,0-.277-.853l-4.1-.6a1.5,1.5,0,0,1-1.13-.821L12.449,4.594a.516.516,0,0,0-.9,0L9.719,8.308a1.5,1.5,0,0,1-1.13.82l-4.1.6a.5.5,0,0,0-.277.853L7.18,13.468A1.5,1.5,0,0,1,7.611,14.8l-.7,4.082a.5.5,0,0,0,.726.527L11.3,17.477a1.5,1.5,0,0,1,1.4,0Z"></path></g></svg>`;
        case "fa fa-heart":
          return `<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M349.6 64c-36.4 0-70.7 16.7-93.6 43.9C233.1 80.7 198.8 64 162.4 64 97.9 64 48 114.2 48 179.1c0 79.5 70.7 143.3 177.8 241.7L256 448l30.2-27.2C393.3 322.4 464 258.6 464 179.1 464 114.2 414.1 64 349.6 64zm-80.8 329.3l-4.2 3.9-8.6 7.8-8.6-7.8-4.2-3.9c-50.4-46.3-94-86.3-122.7-122-28-34.7-40.4-63.1-40.4-92.2 0-22.9 8.4-43.9 23.7-59.3 15.2-15.4 36-23.8 58.6-23.8 26.1 0 52 12.2 69.1 32.5l24.5 29.1 24.5-29.1c17.1-20.4 43-32.5 69.1-32.5 22.6 0 43.4 8.4 58.7 23.8 15.3 15.4 23.7 36.5 23.7 59.3 0 29-12.5 57.5-40.4 92.2-28.8 35.7-72.3 75.7-122.8 122z"></path></svg>`;
        default:
          return "🔔";
      }
    }

    // ---------- NEWS ROLL ----------
    (function initRoll(){
      if(!cfg.rolls || !cfg.rolls.length) return;
      var roll = cfg.rolls[0]; // use latest active
      if ((isMobile() && !allowMobile(roll)) || (!isMobile() && !allowDesktop(roll))) return;

      var wrap = h("div",{class:"aplu-push-self-host-apsh-wrap aplu-push-self-host-apsh-roll-wrap"});
      if(roll.widget_placement === "bottom-left") wrap.classList.add("aplu-push-self-host-apsh-roll-left");

      var card = h("div",{class:"aplu-push-self-host-apsh-card aplu-push-self-host-apsh-roll-card"});
      var header = h("div",{class:"aplu-push-self-host-apsh-header",style:{background:roll.theme_color||"#fd683e"}},[
        h("div",{},[]),
        h("div",{},[
        h("div",{ style:{ textAlign: 'center' }, html: esc(roll.title || "News Roll") }),
            h("small",{},[
                h("a",{
                href:"https://aplu.io/",
                target:"_blank",
                rel:"noopener noreferrer",
                style:{ color:'#fff', textDecoration:'none' }
                },"Powered by Aplu.io")
            ])
        ]),
        h("button",{class:"aplu-push-self-host-apsh-close",onclick:function(){card.style.display="none"}}, "×")
      ]);
      var body = h("div",{class:"aplu-push-self-host-apsh-body"},[]);
      card.appendChild(header); card.appendChild(body);

      var ico = h("div",{
        class:"aplu-push-self-host-apsh-roll-btn",
        style:{background:roll.theme_color||"#fd683e"},
        onclick:function(){
          card.style.display = (card.style.display==="block")?"none":"block";
        },
        html: getIconSvg(roll.icon)
      });


      wrap.appendChild(card); wrap.appendChild(ico); B.appendChild(wrap);

      function fmtDate(ms){
        if(!ms) return "";
        var d = new Date(ms);
        if (isNaN(d.getTime())) return "";
        return d.toLocaleDateString(undefined, { day:'2-digit', month:'short', year:'numeric' });
    }

      fetchFeed(roll.feed_url).then(function(items){
        if(!items.length){ body.innerHTML = '<div class="aplu-push-self-host-apsh-row">No items found.</div>'; return; }
        body.innerHTML="";
        limit(items, 8).forEach(function(it){
          var row=h("div",{class:"aplu-push-self-host-apsh-row"},[
            h("img",{src:it.image||"",class:"aplu-push-self-host-apsh-thumb",alt:""}),
            h("div",{},[ h("a",{href:it.link||"#",target:"_blank",rel:"noopener",class:"aplu-push-self-host-apsh-item-link"},esc(crop(it.title||"Untitled",80))), fmtDate(it.date)
            ? h("span",{
                class:"aplu-push-self-host-apsh-date",
                style:{ marginLeft:'auto', fontSize:'12px', color:'#888' }
            }, fmtDate(it.date))
            : null ])
          ]);
          body.appendChild(row);
        });
      });
    })();

    // ---------- NEWS FLASK (modal with triggers) ----------
    (function initFlask(){
      if(!cfg.flasks || !cfg.flasks.length) return;
      var flask = cfg.flasks[0];
      if ((isMobile() && !allowMobile(flask)) || (!isMobile() && !allowDesktop(flask))) return;

      var storageKey = "apsh_flask_"+site+"_"+flask.id+"_nextAt";
      function canShowNow(){
        var t = +localStorage.getItem(storageKey) || 0;
        return Date.now() >= t;
      }
      function scheduleNext(){
        var min = parseInt(flask.show_again_after_minutes||5,10);
        var nextAt = Date.now() + Math.max(0,min)*60*1000;
        try{ localStorage.setItem(storageKey, String(nextAt)); }catch(_){}
      }

      var mask = h("div",{class:"aplu-push-self-host-apsh-wrap aplu-push-self-host-apsh-flask-mask"});
      var card = h("div",{class:"aplu-push-self-host-apsh-card aplu-push-self-host-apsh-flask"});
      var header = h("div", { class: "aplu-push-self-host-apsh-header", style: { background: flask.theme_color || "#fd683e" }},
      [
        h("div", { html: esc(flask.title || "News Flask"), style: { width: "100%", textAlign: "center"} }),
        h("button", { class: "aplu-push-self-host-apsh-close", onclick: function() { hideFlask(); } }, "×")
      ]);

      var body = h("div",{class:"aplu-push-self-host-apsh-body-flask"},[
        h("div",{class:"aplu-push-self-host-apsh-grid",id:"apsh-grid"},[])
      ]);
      card.appendChild(header); card.appendChild(body); mask.appendChild(card); B.appendChild(mask);

      function showFlask(){
        if(!canShowNow()) return;
        mask.style.display="flex";
      }
      function hideFlask(){
        mask.style.display="none";
        scheduleNext();
      }

      // Triggers
      var armedTime = false, armedScroll=false, armedExit=false;

      if (flask.after_seconds) {
        var ms = Math.max(1, parseInt(flask.after_seconds,10))*1000;
        setTimeout(function(){ if(canShowNow()) showFlask(); }, ms);
        armedTime=true;
      }
      if (flask.scroll_down) {
        var onScroll = once(function(){
          if(canShowNow()) showFlask();
          W.removeEventListener("scroll", handler, {passive:true});
        });
        var handler=function(){
          if (W.scrollY >= W.innerHeight) onScroll();
        };
        W.addEventListener("scroll", handler, {passive:true});
        armedScroll=true;
      }
      if (flask.exit_intent) {
        var leaveHandler=function(e){
          if(e.clientY <= 0 || e.clientY < 10){
            if(canShowNow()) showFlask();
            D.removeEventListener("mouseout", leaveHandler);
          }
        };
        D.addEventListener("mouseout", leaveHandler);
        armedExit=true;
        // also tab hide:
        D.addEventListener("visibilitychange", function(){
          if(D.visibilityState==="hidden" && canShowNow()) showFlask();
        });
      }

      // Fetch 4 items
      fetchFeed(flask.feed_url).then(function(items){
        var grid = D.getElementById("apsh-grid"); if(!grid) return;
        grid.innerHTML="";
        var list = limit(items, 4);
        if(!list.length){ grid.innerHTML = '<div>No items found.</div>'; return; }
        list.forEach(function(it){
          var box = h("div",{class:"aplu-push-self-host-apsh-grid-item"},[
            h("img",{src:it.image||"",alt:""}),
            h("h3",{},esc(crop(it.title||"Untitled",80))),
            h("p",{},esc(crop((it.description||"").replace(/<[^>]+>/g,""),80)))
          ]);
          box.addEventListener("click", function(){ W.open(it.link||"#","_blank","noopener"); });
          grid.appendChild(box);
        });
      });

      // Fallback: if no triggers selected, default to exit intent once
      if(!armedTime && !armedScroll && !armedExit && canShowNow()){
        D.addEventListener("mouseleave", function(){ showFlask(); }, {once:true});
      }
    })();

    // ---------- BOTTOM SLIDER ----------
    function randGloss(){
      var g = [
        {backgroundImage:'linear-gradient(135deg,#ff7a7a,#ff3d3d)'},
        {backgroundImage:'linear-gradient(135deg,#ffb86c,#ff8a00)'},
        {backgroundImage:'linear-gradient(135deg,#7ad1ff,#1aa3ff)'},
        {backgroundImage:'linear-gradient(135deg,#9b7aff,#5a31ff)'},
        {backgroundImage:'linear-gradient(135deg,#6ee7b7,#10b981)'},
        {backgroundImage:'linear-gradient(135deg,#f472b6,#ec4899)'},
        {backgroundImage:'linear-gradient(135deg,#facc15,#f59e0b)'}
      ];
      return g[Math.floor(Math.random()*g.length)];
    }

    // extract categories from RSS/Atom
    function extractCategories(node){
      try{
        return Array.from(node.querySelectorAll("category")).map(function(c){
          return ((c.getAttribute && c.getAttribute("term")) || c.textContent || "").trim();
        }).filter(Boolean);
      }catch(_){ return []; }
    }

    (function initBottomSlider(){
      if(!cfg.sliders || !cfg.sliders.length) return;
      var sl = cfg.sliders[0];
      if ((isMobile() && !allowMobile(sl)) || (!isMobile() && !allowDesktop(sl))) return;

      var bar = h("div",{class:"aplu-push-self-host-apsh-wrap aplu-push-self-host-apsh-slider",style:{borderTopColor: sl.theme_color||"#000"}});
      var track = h("div",{class:"aplu-push-self-host-apsh-slider-track"});
      var close = h("span",{class:"aplu-push-self-host-apsh-slider-x",onclick:function(){ bar.style.display="none"; }}, "×");
      var prev = h("button",{class:"aplu-push-self-host-apsh-arrow aplu-push-self-host-apsh-prev",style:{background: sl.theme_color||"#000", borderColor: sl.theme_color||"#000"}, onclick:function(){ track.scrollBy({left:-320,behavior:"smooth"}) }}, "‹");
      var next = h("button",{class:"aplu-push-self-host-apsh-arrow aplu-push-self-host-apsh-next",style:{background: sl.theme_color||"#000", borderColor: sl.theme_color||"#000"}, onclick:function(){ track.scrollBy({left:320,behavior:"smooth"}) }}, "›");
      bar.appendChild(prev); bar.appendChild(next); bar.appendChild(close); bar.appendChild(track); B.appendChild(bar);

      fetchFeed(sl.feed_url).then(function(items){
        if(!items.length){ return; }
        var list = items.slice();
        if ((sl.mode||"latest")==="random") list = shuffle(list);
        var count = Math.max(1, parseInt(sl.posts_count||8,10));
        list = limit(list, count);

        if(!list.length) return;
        list.forEach(function(it){
          var item = h("div",{class:"aplu-push-self-host-apsh-slider-item"},[
            h("img",{src:it.image||"",alt:""}),
            h("div",{class:"aplu-push-self-host-apsh-slider-text"},[
              h("a",{href:it.link||"#",target:"_blank",rel:"noopener"},esc(crop(it.title||"Untitled",70)))
            ])
          ]);

          var firstCat = (it.categories && it.categories[0]) || "";
          if(firstCat){
            item.appendChild(
              h("span",
                { class:"aplu-push-self-host-apsh-cat-badge", style: randGloss() },
                esc(crop(firstCat, 18))
              )
            );
          }

          track.appendChild(item);
        });

        bar.style.display="block";
      });
    })();

  }catch(e){ /* swallow to not break host page */ }
}

// bootstrap immediately
apluselfhost(APSH_CFG);

// optional: expose for debugging
window.apluselfhost = apluselfhost;

})();
@endif