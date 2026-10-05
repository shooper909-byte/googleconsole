from PIL import Image, ImageDraw, ImageFont, ImageFilter
import numpy as np
from scipy import ndimage
src=Image.open('tirz-front-back.png').convert('RGB')
a=np.asarray(src).astype(float)
H,W,_=a.shape
def textmask(x0,y0,x1,y1):
    s=a[y0:y1,x0:x1]
    m=(s.min(axis=2)>95)|(((s[:,:,0]-s[:,:,1])>35)&((s[:,:,2]-s[:,:,1])>35)&(s[:,:,0]>70))
    m=ndimage.binary_dilation(m,iterations=6)
    full=np.zeros((H,W),bool); full[y0:y1,x0:x1]=m; return full
def erase(m,ytop,ybot):
    # per-column vertical interpolation between clean rows ytop and ybot, plus texture
    out=a.copy(); rng=np.random.default_rng(1)
    top=a[ytop-3:ytop+1].mean(0); bot=a[ybot:ybot+4].mean(0)
    tex=a[ybot+2:ybot+2+(ybot-ytop)]  # texture source below
    for y in range(ytop,ybot):
        t=(y-ytop)/(ybot-ytop); base=top*(1-t)+bot*t
        row=m[y]
        noise=rng.normal(0,5.5,(W,1))*np.array([1,0.95,1.05])
        out[y,row]=base[row]+noise[row]
    return out
m1=textmask(258,782,650,850)   # TIRZEPATIDE 10 MG
m2=textmask(775,720,1072,796)  # 20260609
a=erase(m1,778,854)
a=erase(m2,716,800)
img=Image.fromarray(np.clip(a,0,255).astype('uint8'))
# soften erased region edges slightly
bl=img.filter(ImageFilter.GaussianBlur(1.2))
mm=Image.fromarray(((m1|m2)*255).astype('uint8')).filter(ImageFilter.GaussianBlur(2))
img=Image.composite(bl,img,mm)

WHITE=(242,240,240); MAG=(218,14,243)
def draw_fit(parts,cx,cy,maxw,cap,font):
    # render on big layer, measure, scale to cap height then constrain width
    size=200; f=ImageFont.truetype(font,size)
    capH=f.getbbox('H')[3]-f.getbbox('H')[1]
    widths=[f.getlength(t) for t,_ in parts]; total=sum(widths)
    layer=Image.new('RGBA',(int(total)+40,size+80),(0,0,0,0)); d=ImageDraw.Draw(layer); x=20
    for (t,c),w in zip(parts,widths): d.text((x,20),t,font=f,fill=c); x+=w
    layer=layer.crop(layer.getbbox())
    sc=cap/capH; nw=int(layer.width*sc); nh=int(layer.height*sc)
    if nw>maxw: nw=maxw  # condense horizontally if needed
    layer=layer.resize((nw,nh),Image.LANCZOS)
    img.paste(layer,(int(cx-nw/2),int(cy-nh/2)),layer)
    return nw,nh
print(draw_fit([('MOTS-C ',WHITE),('10 MG',MAG)],452,816,385,53,'oswald600.ttf'))
print(draw_fit([('TESTING',MAG)],922,746,290,40,'oswald600.ttf'))
print(draw_fit([('RESULTS SOON',WHITE)],922,790,290,22,'oswald500.ttf'))
img.save('batch3/mots-c-10mg-front-back-testing-results-soon.png',optimize=True)
img.crop((230,520,1110,920)).save('batch3/mots-c-10mg_check.png')
