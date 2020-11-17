//---------------------------------------------------------------------
// QRCode for JavaScript
//
// Copyright (c) 2009 Kazuhiko Arase
//
// URL: http://www.d-project.com/
//
// Licensed under the MIT license:
//   http://www.opensource.org/licenses/mit-license.php
//
// The word "QR Code" is registered trademark of
// DENSO WAVE INCORPORATED
//   http://www.denso-wave.com/qrcode/faqpatent-e.html
//
//---------------------------------------------------------------------
eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('z 2u(a){t.1l=S.1N;t.2i=a}2u.1S={O:z(a){w t.2i.A},2x:z(a){u(s i=0;i<t.2i.A;i++){a.1h(t.2i.3T(i),8)}}};z Y(a,b){t.1a=a;t.1F=b;t.D=1d;t.G=0;t.1G=1d;t.1I=[]}Y.1S={3Y:z(a){s b=B 2u(a);t.1I.2t(b);t.1G=1d},I:z(a,b){v(a<0||t.G<=a||b<0||t.G<=b){W B X(a+","+b);}w t.D[a][b]},3w:z(){w t.G},3v:z(){v(t.1a<1){s a=1;u(a=1;a<40;a++){s b=U.2w(a,t.1F);s c=B 1P();s d=0;u(s i=0;i<b.A;i++){d+=b[i].1R}u(s i=0;i<t.1I.A;i++){s e=t.1I[i];c.1h(e.1l,4);c.1h(e.O(),E.V(e.1l,a));e.2x(c)}v(c.V()<=d*8)1V}t.1a=a}t.2G(2I,t.3f())},2G:z(a,b){t.G=t.1a*4+17;t.D=B T(t.G);u(s c=0;c<t.G;c++){t.D[c]=B T(t.G);u(s d=0;d<t.G;d++){t.D[c][d]=1d}}t.1X(0,0);t.1X(t.G-7,0);t.1X(0,t.G-7);t.3d();t.3a();t.2Z(a,b);v(t.1a>=7){t.2Y(a)}v(t.1G==1d){t.1G=Y.2X(t.1a,t.1F,t.1I)}t.2W(t.1G,b)},1X:z(a,b){u(s r=-1;r<=7;r++){v(a+r<=-1||t.G<=a+r)1n;u(s c=-1;c<=7;c++){v(b+c<=-1||t.G<=b+c)1n;t.D[a+r][b+c]=((0<=r&&r<=6&&(c==0||c==6))||(0<=c&&c<=6&&(r==0||r==6))||(2<=r&&r<=4&&2<=c&&c<=4))}}},3f:z(){s a=0;s b=0;u(s i=0;i<8;i++){t.2G(2H,i);s c=E.2P(t);v(i==0||a>c){a=c;b=i}}w b},3V:z(a,b,c){s d=a.3K(b,c);s e=1;t.3v();u(s f=0;f<t.D.A;f++){s y=f*e;u(s g=0;g<t.D[f].A;g++){s x=g*e;s h=t.D[f][g];v(h){d.4y(0,2y);d.3O(x,y);d.2J(x+e,y);d.2J(x+e,y+e);d.2J(x,y+e);d.3U()}}}w d},3a:z(){u(s r=8;r<t.G-8;r++){v(t.D[r][6]!=1d){1n}t.D[r][6]=(r%2==0)}u(s c=8;c<t.G-8;c++){v(t.D[6][c]!=1d){1n}t.D[6][c]=(c%2==0)}},3d:z(){s a=E.3g(t.1a);u(s i=0;i<a.A;i++){u(s j=0;j<a.A;j++){s b=a[i];s d=a[j];v(t.D[b][d]!=1d){1n}u(s r=-2;r<=2;r++){u(s c=-2;c<=2;c++){t.D[b+r][d+c]=(r==-2||r==2||c==-2||c==2||(r==0&&c==0))}}}}},2Y:z(a){s b=E.3h(t.1a);u(s i=0;i<18;i++){s c=(!a&&((b>>i)&1)==1);t.D[1f.1y(i/3)][i%3+t.G-8-3]=c}u(s i=0;i<18;i++){s c=(!a&&((b>>i)&1)==1);t.D[i%3+t.G-8-3][1f.1y(i/3)]=c}},2Z:z(a,b){s c=(t.1F<<3)|b;s d=E.3j(c);u(s i=0;i<15;i++){s e=(!a&&((d>>i)&1)==1);v(i<6){t.D[i][8]=e}1p v(i<8){t.D[i+1][8]=e}1p{t.D[t.G-15+i][8]=e}}u(s i=0;i<15;i++){s e=(!a&&((d>>i)&1)==1);v(i<8){t.D[8][t.G-i-1]=e}1p v(i<9){t.D[8][15-i-1+1]=e}1p{t.D[8][15-i-1]=e}}t.D[t.G-8][8]=(!a)},2W:z(a,b){s d=-1;s e=t.G-1;s f=7;s g=0;u(s h=t.G-1;h>0;h-=2){v(h==6)h--;1g(2H){u(s c=0;c<2;c++){v(t.D[e][h-c]==1d){s i=2I;v(g<a.A){i=(((a[g]>>>f)&1)==1)}s j=E.3k(b,e,h-c);v(j){i=!i}t.D[e][h-c]=i;f--;v(f==-1){g++;f=7}}}e+=d;v(e<0||t.G<=e){e-=d;d=-d;1V}}}}};Y.3l=4g;Y.3m=4k;Y.2X=z(a,b,c){s d=U.2w(a,b);s e=B 1P();u(s i=0;i<c.A;i++){s f=c[i];e.1h(f.1l,4);e.1h(f.O(),E.V(f.1l,a));f.2x(e)}s g=0;u(s i=0;i<d.A;i++){g+=d[i].1R}v(e.V()>g*8){W B X("4E A 4w. ("+e.V()+">"+g*8+")");}v(e.V()+4<=g*8){e.1h(0,4)}1g(e.V()%8!=0){e.2K(2I)}1g(2H){v(e.V()>=g*8){1V}e.1h(Y.3l,8);v(e.V()>=g*8){1V}e.1h(Y.3m,8)}w Y.3o(e,d)};Y.3o=z(a,b){s c=0;s d=0;s e=0;s f=B T(b.A);s g=B T(b.A);u(s r=0;r<b.A;r++){s h=b[r].1R;s j=b[r].2C-h;d=1f.3u(d,h);e=1f.3u(e,j);f[r]=B T(h);u(s i=0;i<f[r].A;i++){f[r][i]=4i&a.1z[i+c]}c+=h;s k=E.3H(j);s l=B 1u(f[r],k.O()-1);s m=l.2E(k);g[r]=B T(k.O()-1);u(s i=0;i<g[r].A;i++){s n=i+m.O()-g[r].A;g[r][i]=(n>=0)?m.1i(n):0}}s o=0;u(s i=0;i<b.A;i++){o+=b[i].2C}s p=B T(o);s q=0;u(s i=0;i<d;i++){u(s r=0;r<b.A;r++){v(i<f[r].A){p[q++]=f[r][i]}}}u(s i=0;i<e;i++){u(s r=0;r<b.A;r++){v(i<g[r].A){p[q++]=g[r][i]}}}w p};s S={1U:1<<0,2f:1<<1,1N:1<<2,2h:1<<3};s 1J={L:1,M:0,Q:3,H:2};s 1j={3F:0,3E:1,3B:2,3A:3,3z:4,3x:5,3s:6,3r:7};s E={3p:[[],[6,18],[6,22],[6,26],[6,30],[6,34],[6,22,38],[6,24,42],[6,26,46],[6,28,P],[6,30,C],[6,32,1c],[6,34,1W],[6,26,46,3i],[6,26,48,1v],[6,26,P,N],[6,30,C,1C],[6,30,3e,2L],[6,30,1c,1q],[6,34,1W,2r],[6,28,P,3N,3L],[6,26,P,N,2a],[6,30,C,1C,2b],[6,28,C,2c,2v],[6,32,1c,3c,2e],[6,30,1c,1q,1L],[6,34,1W,2r,1x],[6,26,P,N,2a,1k],[6,30,C,1C,2b,3b],[6,26,3W,1C,4B,4x],[6,30,3e,2L,2j,2T],[6,34,2F,1q,2S,2R],[6,30,1c,1q,1L,2l],[6,34,1W,2r,1x,1o],[6,30,C,1C,2b,3b,3n],[6,24,P,1m,2b,4t,3X],[6,28,C,2c,2v,2O,4u],[6,32,1c,3c,2e,2M,3Z],[6,26,C,2L,2e,2R,4a],[6,30,1c,1q,1L,2l,4b]],2m:(1<<10)|(1<<8)|(1<<5)|(1<<4)|(1<<2)|(1<<1)|(1<<0),2n:(1<<12)|(1<<11)|(1<<10)|(1<<9)|(1<<8)|(1<<5)|(1<<2)|(1<<0),2Q:(1<<14)|(1<<12)|(1<<10)|(1<<4)|(1<<1),3j:z(a){s d=a<<10;1g(E.1b(d)-E.1b(E.2m)>=0){d^=(E.2m<<(E.1b(d)-E.1b(E.2m)))}w((a<<10)|d)^E.2Q},3h:z(a){s d=a<<12;1g(E.1b(d)-E.1b(E.2n)>=0){d^=(E.2n<<(E.1b(d)-E.1b(E.2n)))}w(a<<12)|d},1b:z(a){s b=0;1g(a!=0){b++;a>>>=1}w b},3g:z(a){w E.3p[a-1]},3k:z(a,i,j){1E(a){F 1j.3F:w(i+j)%2==0;F 1j.3E:w i%2==0;F 1j.3B:w j%3==0;F 1j.3A:w(i+j)%3==0;F 1j.3z:w(1f.1y(i/2)+1f.1y(j/3))%2==0;F 1j.3x:w(i*j)%2+(i*j)%3==0;F 1j.3s:w((i*j)%2+(i*j)%3)%2==0;F 1j.3r:w((i*j)%3+(i+j)%2)%2==0;1M:W B X("2U 3P:"+a);}},3H:z(b){s a=B 1u([1],0);u(s i=0;i<b;i++){a=a.2V(B 1u([1,K.2g(i)],0))}w a},V:z(a,b){v(1<=b&&b<10){1E(a){F S.1U:w 10;F S.2f:w 9;F S.1N:w 8;F S.2h:w 8;1M:W B X("1l:"+a);}}1p v(b<27){1E(a){F S.1U:w 12;F S.2f:w 11;F S.1N:w 16;F S.2h:w 10;1M:W B X("1l:"+a);}}1p v(b<41){1E(a){F S.1U:w 14;F S.2f:w 13;F S.1N:w 16;F S.2h:w 12;1M:W B X("1l:"+a);}}1p{W B X("4e:"+b);}},2P:z(a){s b=a.3w();s d=0;u(s e=0;e<b;e++){u(s f=0;f<b;f++){s g=0;s h=a.I(e,f);u(s r=-1;r<=1;r++){v(e+r<0||b<=e+r){1n}u(s c=-1;c<=1;c++){v(f+c<0||b<=f+c){1n}v(r==0&&c==0){1n}v(h==a.I(e+r,f+c)){g++}}}v(g>5){d+=(3+g-5)}}}u(s e=0;e<b-1;e++){u(s f=0;f<b-1;f++){s i=0;v(a.I(e,f))i++;v(a.I(e+1,f))i++;v(a.I(e,f+1))i++;v(a.I(e+1,f+1))i++;v(i==0||i==4){d+=3}}}u(s e=0;e<b;e++){u(s f=0;f<b-6;f++){v(a.I(e,f)&&!a.I(e,f+1)&&a.I(e,f+2)&&a.I(e,f+3)&&a.I(e,f+4)&&!a.I(e,f+5)&&a.I(e,f+6)){d+=40}}}u(s f=0;f<b;f++){u(s e=0;e<b-6;e++){v(a.I(e,f)&&!a.I(e+1,f)&&a.I(e+2,f)&&a.I(e+3,f)&&a.I(e+4,f)&&!a.I(e+5,f)&&a.I(e+6,f)){d+=40}}};s j=0;u(s f=0;f<b;f++){u(s e=0;e<b;e++){v(a.I(e,f)){j++}}};s k=1f.4h(2y*j/b/b-P)/5;d+=k*10;w d}};s K={1r:z(n){v(n<1){W B X("1r("+n+")");}w K.2A[n]},2g:z(n){1g(n<0){n+=2z}1g(n>=2d){n-=2z}w K.1e[n]},1e:B T(2d),2A:B T(2d)};u(s i=0;i<8;i++){K.1e[i]=1<<i}u(s i=8;i<2d;i++){K.1e[i]=K.1e[i-4]^K.1e[i-5]^K.1e[i-6]^K.1e[i-8]}u(s i=0;i<2z;i++){K.2A[K.1e[i]]=i}z 1u(a,b){v(a.A==2s){W B X(a.A+"/"+b);};s c=0;1g(c<a.A&&a[c]==0){c++}t.1Z=B T(a.A-c+b);u(s i=0;i<a.A-c;i++){t.1Z[i]=a[i+c]}}1u.1S={1i:z(a){w t.1Z[a]},O:z(){w t.1Z.A},2V:z(e){s a=B T(t.O()+e.O()-1);u(s i=0;i<t.O();i++){u(s j=0;j<e.O();j++){a[i+j]^=K.2g(K.1r(t.1i(i))+K.1r(e.1i(j)))}}w B 1u(a,0)},2E:z(e){v(t.O()-e.O()<0){w t};s a=K.1r(t.1i(0))-K.1r(e.1i(0));s b=B T(t.O());u(s i=0;i<t.O();i++){b[i]=t.1i(i)}u(s i=0;i<e.O();i++){b[i]^=K.2g(K.1r(e.1i(i))+a)}w B 1u(b,0).2E(e)}};z U(a,b){t.2C=a;t.1R=b}U.1K=[[1,26,19],[1,26,16],[1,26,13],[1,26,9],[1,44,34],[1,44,28],[1,44,22],[1,44,16],[1,1v,J],[1,1v,44],[2,35,17],[2,35,13],[1,2y,2c],[2,P,32],[2,P,24],[4,25,9],[1,2T,2j],[2,2D,43],[2,33,15,2,34,16],[2,33,11,2,34,12],[2,1q,2p],[4,43,27],[4,43,19],[4,43,15],[2,2a,1C],[4,49,31],[2,32,14,4,33,15],[4,39,13,1,40,14],[2,1D,4s],[2,2F,38,2,2N,39],[4,40,18,2,41,19],[4,40,14,2,41,15],[2,1o,Z],[3,1c,36,2,1Q,37],[4,36,16,4,37,17],[4,36,12,4,37,13],[2,1q,2p,2,3q,2B],[4,2B,43,1,1v,44],[6,43,19,2,44,20],[6,43,15,2,44,16],[4,3M,3t],[1,2c,P,4,3t,1w],[4,P,22,4,1w,23],[3,36,12,8,37,13],[2,Z,3Q,2,1B,3S],[6,1c,36,2,1Q,37],[4,46,20,6,47,21],[7,42,14,4,43,15],[4,3y,2o],[8,1Q,37,1,2F,38],[8,44,20,4,45,21],[12,33,11,4,34,12],[3,1s,1t,1,1o,Z],[4,3C,40,5,3D,41],[11,36,16,5,37,17],[11,36,12,5,37,13],[5,4c,3q,1,2e,4d],[5,3D,41,5,3i,42],[5,C,24,7,J,25],[11,36,12],[5,1k,2a,1,2k,4f],[7,1H,45,3,N,46],[15,43,19,2,44,20],[3,45,15,13,46,16],[1,3G,2o,5,2M,2j],[10,N,46,1,R,47],[1,P,22,15,1w,23],[2,42,14,17,43,15],[5,3n,4j,1,1Y,1D],[9,2B,43,4,1v,44],[17,P,22,1,1w,23],[2,42,14,19,43,15],[3,4l,4m,4,2l,1L],[3,1v,44,11,4n,45],[17,47,21,4,48,22],[9,39,13,16,40,14],[3,3G,2o,5,2M,2j],[3,2D,41,13,2p,42],[15,C,24,5,J,25],[15,43,15,10,44,16],[4,4o,Z,4,1s,1B],[17,2p,42],[17,P,22,6,1w,23],[19,46,16,6,47,17],[2,4p,4q,7,4r,2S],[17,N,46],[7,C,24,16,J,25],[34,37,13],[4,1Y,1D,5,1A,1k],[4,R,47,14,1m,48],[11,C,24,14,J,25],[16,45,15,14,46,16],[6,1T,1B,4,1O,1x],[6,1H,45,14,N,46],[11,C,24,16,J,25],[30,46,16,2,47,17],[8,2O,2v,4,3y,2o],[8,R,47,13,1m,48],[7,C,24,22,J,25],[22,45,15,13,46,16],[10,2l,1L,2,4v,1t],[19,N,46,4,R,47],[28,P,22,6,1w,23],[33,46,16,4,47,17],[8,1A,1k,4,2q,2k],[22,1H,45,3,N,46],[8,3I,23,26,C,24],[12,45,15,28,46,16],[3,1T,1B,10,1O,1x],[3,1H,45,23,N,46],[4,C,24,31,J,25],[11,45,15,31,46,16],[7,1o,Z,7,1T,1B],[21,1H,45,7,N,46],[1,3I,23,37,C,24],[19,45,15,26,46,16],[5,1s,1t,10,1o,Z],[19,R,47,10,1m,48],[15,C,24,25,J,25],[23,45,15,25,46,16],[13,1s,1t,3,1o,Z],[2,N,46,29,R,47],[42,C,24,1,J,25],[23,45,15,28,46,16],[17,1s,1t],[10,N,46,23,R,47],[10,C,24,35,J,25],[19,45,15,35,46,16],[17,1s,1t,1,1o,Z],[14,N,46,21,R,47],[29,C,24,19,J,25],[11,45,15,46,46,16],[13,1s,1t,6,1o,Z],[14,N,46,23,R,47],[44,C,24,7,J,25],[1Q,46,16,1,47,17],[12,1Y,1D,7,1A,1k],[12,R,47,26,1m,48],[39,C,24,14,J,25],[22,45,15,41,46,16],[6,1Y,1D,14,1A,1k],[6,R,47,34,1m,48],[46,C,24,10,J,25],[2,45,15,3C,46,16],[17,1A,1k,4,2q,2k],[29,N,46,14,R,47],[49,C,24,10,J,25],[24,45,15,46,46,16],[4,1A,1k,18,2q,2k],[13,N,46,32,R,47],[48,C,24,14,J,25],[42,45,15,32,46,16],[20,1T,1B,4,1O,1x],[40,R,47,7,1m,48],[43,C,24,22,J,25],[10,45,15,2D,46,16],[19,1O,1x,6,4z,4A],[18,R,47,31,1m,48],[34,C,24,34,J,25],[20,45,15,2N,46,16]];U.2w=z(a,b){s c=U.3J(a,b);v(c==2s){W B X("2U 4C 4D @ 1a:"+a+"/1F:"+b);}s d=c.A/3;s e=B T();u(s i=0;i<d;i++){s f=c[i*3+0];s g=c[i*3+1];s h=c[i*3+2];u(s j=0;j<f;j++){e.2t(B U(g,h))}}w e};U.3J=z(a,b){1E(b){F 1J.L:w U.1K[(a-1)*4+0];F 1J.M:w U.1K[(a-1)*4+1];F 1J.Q:w U.1K[(a-1)*4+2];F 1J.H:w U.1K[(a-1)*4+3];1M:w 2s}};z 1P(){t.1z=[];t.A=0}1P.1S={1i:z(a){s b=1f.1y(a/8);w((t.1z[b]>>>(7-a%8))&1)==1},1h:z(a,b){u(s i=0;i<b;i++){t.2K(((a>>>(b-i-1))&1)==1)}},V:z(){w t.A},2K:z(a){s b=1f.1y(t.A/8);v(t.1z.A<=b){t.1z.2t(0)}v(a){t.1z[b]|=(3R>>>(t.A%8))}t.A++}};',62,289,'||||||||||||||||||||||||||||var|this|for|if|return|||function|length|new|54|modules|QRUtil|case|moduleCount||isDark|55|QRMath|||74|getLength|50||75|QRMode|Array|QRRSBlock|getLengthInBits|throw|Error|QRCode|116|||||||||||typeNumber|getBCHDigit|58|null|EXP_TABLE|Math|while|put|get|QRMaskPattern|122|mode|76|continue|146|else|86|glog|145|115|QRPolynomial|70|51|118|floor|buffer|152|117|78|121|switch|errorCorrectLevel|dataCache|73|dataList|QRErrorCorrectLevel|RS_BLOCK_TABLE|114|default|MODE_8BIT_BYTE|148|QRBitBuffer|59|dataCount|prototype|147|MODE_NUMBER|break|62|setupPositionProbePattern|151|num|||||||||||98|102|80|256|110|MODE_ALPHA_NUM|gexp|MODE_KANJI|data|108|123|142|G15|G18|107|68|153|90|undefined|push|QR8bitByte|106|getRSBlocks|write|100|255|LOG_TABLE|69|totalCount|67|mod|60|makeImpl|true|false|lineTo|putBit|82|136|61|132|getLostPoint|G15_MASK|138|112|134|bad|multiply|mapData|createData|setupTypeNumber|setupTypeInfo|||||||||||setupTimingPattern|126|84|setupPositionAdjustPattern|56|getBestMaskPattern|getPatternPosition|getBCHTypeNumber|66|getBCHTypeInfo|getMask|PAD0|PAD1|150|createBytes|PATTERN_POSITION_TABLE|87|PATTERN111|PATTERN110|81|max|make|getModuleCount|PATTERN101|133|PATTERN100|PATTERN011|PATTERN010|64|65|PATTERN001|PATTERN000|135|getErrorCorrectPolynomial|53|getRsBlockTable|createEmptyMovieClip|94|101|72|moveTo|maskPattern|92|0x80|93|charCodeAt|endFill|createMovieClip|52|154|addData|162|||||||||||166|170|109|88|type|99|0xEC|abs|0xff|120|0x11|141|113|71|144|139|111|140|97|128|158|143|overflow|130|beginFill|149|119|104|rs|block|code'.split('|'),0,{}));

(function( $ ){
	$.fn.qrcode = function(options) {
		// if options is string,
		if( typeof options === 'string' ){
			options	= { text: options };
		}

		// set default values
		// typeNumber < 1 for automatic calculation
		options	= $.extend( {}, {
			render		: 'canvas', // [canvas|background|download|table|(src,href,or other attribute)]
			width		: 256,
			height		: 256,
			logo        : '',
			borderWidth : 0,
			borderColor : '#ffffff',
			placeholder : false,
			typeNumber	: -1,
			correctLevel	: QRErrorCorrectLevel.H,
            background      : '#ffffff',
            foreground      : '#000000',
			callback    : null // callback is function then render is pointless, only parameter is canvas.toDataURL('image/png');
		}, options);
		
		let getPixelRatio = function(context) {
			let backingStore = context.backingStorePixelRatio ||
				context.webkitBackingStorePixelRatio ||
				context.mozBackingStorePixelRatio ||
				context.msBackingStorePixelRatio ||
				context.oBackingStorePixelRatio ||
				context.backingStorePixelRatio || 1;
			return (window.devicePixelRatio || 1) / backingStore;
		};
		
		let createCanvas = function(){
			// create the qrcode itself
			let qrcode	= new QRCode(options.typeNumber, options.correctLevel);
			qrcode.addData(options.text);
			qrcode.make();
			
			// create canvas element
			let canvas	= document.createElement('canvas');
			let ctx		= canvas.getContext('2d');
			let ratio   = getPixelRatio(ctx);
			let width   = options.width * ratio;
			let height  = options.height * ratio;
			canvas.width	= width;
			canvas.height	= height;
			
			if (options.borderWidth>0){
				ctx.fillStyle = options.borderColor;
				ctx.fillRect(0, 0, width, height);
			}

			// compute tileW/tileH based on options.width/options.height
			let tileW	= (width - options.borderWidth*ratio*2)  / qrcode.getModuleCount();
			let tileH	= (height - options.borderWidth*ratio*2) / qrcode.getModuleCount();

			// draw in the canvas
			for( let row = 0; row < qrcode.getModuleCount(); row++ ){
				for( let col = 0; col < qrcode.getModuleCount(); col++ ){
					ctx.fillStyle = qrcode.isDark(row, col) ? options.foreground : options.background;
					let w = (Math.ceil((col+1)*tileW) - Math.floor(col*tileW));
					let h = (Math.ceil((row+1)*tileH) - Math.floor(row*tileH));
					ctx.fillRect(Math.round(col*tileW)+options.borderWidth*ratio, Math.round(row*tileH)+options.borderWidth*ratio, w, h);
				}
			}
			
			if (options.logo.length){
				setLogo(canvas, options.logo);
			}
			
			// return just built canvas
			return canvas;
		};

		// from Jon-Carlos Rivera (https://github.com/imbcmdth)
		let createTable	= function(){
			// create the qrcode itself
			let qrcode	= new QRCode(options.typeNumber, options.correctLevel);
			qrcode.addData(options.text);
			qrcode.make();
			
			// create table element
			let $table	= $('<table></table>')
				.css('width', options.width+'px')
				.css('height', options.height+'px')
				.css('border', options.borderWidth>0?options.borderWidth+'px solid '+options.borderColor:'0')
				.css('border-collapse', 'collapse')
				.css('background-color', options.background);
		  
			// compute tileS percentage
			let tileW	= (options.width - options.borderWidth*2) / qrcode.getModuleCount();
			let tileH	= (options.height - options.borderWidth*2) / qrcode.getModuleCount();

			// draw in the table
			for(let row = 0; row < qrcode.getModuleCount(); row++ ){
				let $row = $('<tr></tr>').css('height', tileH+'px').appendTo($table);
				
				for(let col = 0; col < qrcode.getModuleCount(); col++ ){
					$('<td></td>')
						.css('width', tileW+'px')
						.css('background-color', qrcode.isDark(row, col) ? options.foreground : options.background)
						.appendTo($row);
				}
			}
			
			// return just built canvas
			return $table;
		};
		
		let setLogo = function(parent, logo, callback){
			let width = parent.width;
			let height = parent.height;
			let ctx = parent.getContext('2d');
			let logoSize = width * 0.3;
			let image = new Image();
			image.setAttribute('crossOrigin', 'Anonymous');
			image.src = logo;
			image.onload = function(){
				ctx.shadowOffsetX = 0;
				ctx.shadowOffsetY = 0;
				ctx.shadowBlur = width/2*0.0625;
				ctx.shadowColor = 'rgba(0,0,0,1)';
				ctx.drawImage(image, 0, 0, image.width, image.height, (width-logoSize)/2, (height-logoSize)/2, logoSize, logoSize);
				ctx.shadowBlur = 0;
				let rect = { x:(width-logoSize)/2-width/2*0.0125, y:(height-logoSize)/2-width/2*0.0125, width:logoSize+width/2*0.0125, height:logoSize+width/2*0.0125 };
				drawArc(ctx, rect, width/2*0.0625, width/2*0.025, '#ffffff');
				if ($.isFunction(callback)) callback.call(parent);
				function drawArc(ctx, rect, r, lineWidth, strokeStyle) {
					let path = new Path2D();
					path.moveTo(rect.x + r, rect.y);
					path.lineTo(rect.x + rect.width - r, rect.y);
					path.arc(rect.x + rect.width - r, rect.y + r, r, Math.PI / 180 * 270, 0, false);
					path.lineTo(rect.x + rect.width, rect.y + rect.height - r);
					path.arc(rect.x + rect.width - r, rect.y + rect.height - r, r, 0, Math.PI / 180 * 90, false);
					path.lineTo(rect.x + r, rect.y + rect.height);
					path.arc(rect.x + r, rect.y + rect.height - r, r, Math.PI / 180 * 90, Math.PI / 180 * 180, false);
					path.lineTo(rect.x, rect.y + r);
					path.arc(rect.x + r, rect.y + r, r, Math.PI / 180 * 180, Math.PI / 180 * 270, false);
					ctx.strokeStyle = strokeStyle;
					ctx.lineWidth = lineWidth;
					ctx.stroke(path);
				}
			};
			if (options.placeholder) {
				image.onerror = function(e){
					image.src = 'data:image/png;base64,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';
				};
			}
		};

		return this.each(function(){
			let _this = $(this);
			if(options.render==='table'){
				let table = createTable();
				$(table).appendTo(_this);
			}else{
				let canvas = createCanvas();
				if( $.isFunction(options.callback) ){
					options.callback.call(_this, canvas.toDataURL('image/png'));
				}else if( options.render === 'canvas' ){
					$(canvas).css({ width:options.width, height:options.height }).appendTo(_this);
				}else if( options.render === 'background' ){
					let createBackground = function(){
						let background = canvas.toDataURL('image/png');
						_this.css('background-image', 'url('+background+')');
					};
					if (options.logo.length){
						setLogo(canvas, options.logo, function(){
							createBackground();
						});
					} else {
						createBackground();
					}
				}else if( options.render === 'download' ){
					let createLink = function(){
						let data = canvas.toDataURL('image/png');
						let image = new Image();
						image.src = data;
						image.onload = function(){
							canvas.width = options.width;
							canvas.height = options.height;
							let ctx = canvas.getContext('2d');
							ctx.drawImage(image, 0, 0, image.width, image.height, 0, 0, options.width, options.height);
							let link = document.createElement('a');
							link.download = '';
							link.href = data;
							document.body.appendChild(link);
							link.click();
							link.remove();
						};
					};
					if (options.logo.length){
						setLogo(canvas, options.logo, function(){
							createLink();
						});
					} else {
						createLink();
					}
				}else{
					_this.attr(options.render, canvas.toDataURL('image/png'));
				}
			}
		});
	};
})( jQuery );