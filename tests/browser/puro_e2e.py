"""Optional browser regression. Run only against a local seeded demo instance.
pip install playwright; playwright install chromium
PURO_BASE_URL=http://127.0.0.1:8000 python tests/browser/puro_e2e.py
Creates demo orders; never sends WhatsApp messages or real payments.
"""
import os,re,json,time
from urllib.parse import urlparse
from pathlib import Path
from playwright.sync_api import sync_playwright,expect

ROOT=Path(__file__).resolve().parents[2]
BASE=os.getenv('PURO_BASE_URL','http://127.0.0.1:8000').rstrip('/')
if urlparse(BASE).hostname not in ['127.0.0.1','localhost']:
 raise RuntimeError('Browser regression is restricted to a localhost demo server.')
OUT=Path(os.getenv('PURO_QA_OUTPUT',str(ROOT/'storage/framework/testing/browser')))
OUT.mkdir(parents=True,exist_ok=True)
PHOTO=ROOT/'public/assets/service-photos/shoe-1.jpg'
REF='E2E'+str(time.time_ns())
errors=[];failures=[];checks=[]
def log(s):
 checks.append(s);print(s,flush=True)
def wire(page):
 page.wait_for_function('window.Livewire !== undefined')
def context(browser,viewport=None):
 ctx=browser.new_context(viewport=viewport or {'width':1440,'height':1000})
 ctx.route(re.compile(r'^https?://'),lambda route: route.continue_() if route.request.url.startswith(BASE+'/') else route.abort())
 ctx.on('page',lambda page: page.on('response',lambda r: failures.append({'status':r.status,'path':urlparse(r.url).path}) if r.status>=500 and r.url.startswith(BASE) else None))
 ctx.set_default_timeout(12000)
 return ctx
def login(browser,name,viewport=None):
 ctx=context(browser,viewport)
 page=ctx.new_page();page.on('dialog',lambda d:d.accept())
 page.on('pageerror',lambda e:errors.append(str(e)))
 page.goto(BASE+'/staff/login');wire(page)
 page.get_by_label('Username',exact=True).fill(name)
 page.get_by_label('Password',exact=True).fill('Demo123!')
 page.get_by_role('button',name='Masuk ke dashboard ↗',exact=True).click()
 page.wait_for_url(BASE+'/staff',timeout=15000)
 return ctx,page
def open_order(page,code,service='Woman & Kids Shoes'):
 page.get_by_role('link',name=re.compile(code)).click()
 expect(page.get_by_role('dialog')).to_contain_text(service)
def upload(page,label):
 field=page.get_by_label(label,exact=True)
 field.evaluate("el=>{delete el.dataset.qaUploadComplete;el.addEventListener('livewire-upload-finish',()=>el.dataset.qaUploadComplete='1',{once:true})}")
 field.set_input_files(str(PHOTO))
 expect(field).to_have_attribute('data-qa-upload-complete','1',timeout=20000)
def no_overflow(page,name):
 delta=page.evaluate('document.documentElement.scrollWidth-window.innerWidth')
 assert delta<=1,f'{name}: horizontal overflow {delta}px'
def run():
 with sync_playwright() as pw:
  opts={'headless':True,'args':['--no-sandbox','--disable-dev-shm-usage']}
  if executable:=os.getenv('PURO_BROWSER_EXECUTABLE'):opts['executable_path']=executable
  browser_env=os.environ.copy();browser_env.pop('LD_LIBRARY_PATH',None);opts['env']=browser_env
  browser=pw.chromium.launch(**opts)
  ctx=context(browser)
  guest=ctx.new_page();guest.on('pageerror',lambda e:errors.append(str(e)))
  guest.goto(BASE+'/staff/login');expect(guest.get_by_text('Akun uji coba:',exact=True)).to_be_visible()
  guest.screenshot(path=str(OUT/'app-login.png'))
  guest.goto(BASE);wire(guest);expect(guest.locator('h1')).to_contain_text('Professional')
  guest.screenshot(path=str(OUT/'app-home.png'))
  guest.evaluate("window.__puroNavigateProof='same-document'")
  guest.locator('nav.public-nav').get_by_role('link',name='Layanan',exact=True).click()
  guest.wait_for_url(BASE+'/layanan')
  catalogue=json.loads((ROOT/'database/seeders/catalogue.json').read_text())
  for service in catalogue['services']:
   expect(guest.get_by_role('link',name='Lihat detail '+service['name'],exact=True)).to_have_count(1)
  assert guest.evaluate('window.__puroNavigateProof')=='same-document'
  expect(guest.locator('.public-nav .active')).to_have_css('color','rgb(150, 214, 105)')
  expect(guest.locator('.v3610-category-tabs a').nth(-2)).to_have_text('Cap & Hat Clean')
  guest.screenshot(path=str(OUT/'app-services.png'))
  guest.get_by_role('link',name='Lihat detail Deep Clean',exact=True).click()
  poster=guest.get_by_role('dialog',name='Deep Clean',exact=True)
  expect(poster).to_be_visible();expect(poster.locator('img')).to_be_visible()
  assert poster.locator('img').evaluate('element => element.complete && element.naturalWidth > 0')
  guest.keyboard.press('Escape');expect(poster).not_to_be_visible()
  guest.locator('.v3610-category-tabs').get_by_role('link',name='FAQ',exact=True).click()
  expect(guest.locator('.v3610-category-tabs .active')).to_have_text('FAQ')
  expect(guest.locator('.v3610-category-tabs .active')).to_have_css('color','rgb(150, 214, 105)')
  guest.locator('.services-faq-item summary').first.click()
  expect(guest.locator('.services-faq-item[open]')).to_have_count(1)
  log('PASS: 17 services, poster dialog, active green, FAQ last and wire:navigate')
  guest.goto(BASE+'/lokasi');guest.screenshot(path=str(OUT/'app-locations.png'))
  guest.goto(BASE+'/track');guest.screenshot(path=str(OUT/'app-track.png'))
  with guest.expect_download() as guide_download:
   guest.get_by_role('link',name='Unduh panduan PDF ↓',exact=True).click()
  assert guide_download.value.path().read_bytes().startswith(b'%PDF-')
  guest.goto(BASE+'/book?service=2');wire(guest)
  guest.screenshot(path=str(OUT/'app-book.png'))
  guest.get_by_label('Nama lengkap *',exact=True).fill('Customer Browser Demo')
  guest.get_by_label('WhatsApp / telepon *',exact=True).fill('081234567890')
  guest.get_by_label('Jenis barang *',exact=True).fill('Sneakers putih untuk uji browser')
  guest.locator('input[value="DELIVERY"]').check()
  guest.get_by_label('Alamat antar / jemput *',exact=True).fill('Alamat fiktif pengujian PURO')
  guest.get_by_role('button',name='Lanjut ke pembayaran ↗',exact=True).click()
  guest.wait_for_url(re.compile('/book/PURO-[A-Z0-9]+/checkout'),timeout=20000)
  code=guest.locator('.checkout-code .tracking-token').inner_text().strip()
  expect(guest.locator('.checkout-code')).to_contain_text('Kode pesanan')
  expect(guest.get_by_text('Customer Browser Demo',exact=True)).to_have_count(0)
  assert code in guest.evaluate("localStorage.getItem('puro-recent-orders')")
  guest.close()
  guest=ctx.new_page();guest.on('pageerror',lambda e:errors.append(str(e)))
  guest.goto(BASE);wire(guest)
  guest.get_by_role('link',name='Cek status',exact=True).click()
  tracking=guest.get_by_role('dialog',name='Status pesanan.',exact=True)
  expect(tracking.get_by_text(code,exact=True)).to_be_visible()
  tracking.get_by_role('link',name='Buka status ↗',exact=True).click()
  guest.wait_for_url(BASE+'/track/'+code)
  guest.get_by_role('link',name='Lanjut ke pembayaran ↗',exact=True).click()
  expect(guest.locator('.checkout-code .tracking-token')).to_have_text(code)
  log('PASS: guest booking, same code from checkout and recent orders after closing the tab')
  guest.get_by_label('Nomor referensi transaksi',exact=True).fill(REF)
  upload(guest,'Bukti pembayaran')
  guest.get_by_role('checkbox',name=re.compile('Saya sudah membayar sesuai tagihan')).check()
  guest.get_by_role('button',name='Konfirmasi sudah bayar ↗',exact=True).click()
  guest.wait_for_url(BASE+'/book/'+code+'/selesai',timeout=20000)
  expect(guest.locator('.success-hero .tracking-token')).to_have_text(code)
  guest.get_by_role('link',name='Lacak pesanan ↗',exact=True).click()
  expect(guest.get_by_text('Bukti pembayaran sedang diperiksa',exact=True)).to_be_visible(timeout=20000)
  ac,admin=login(browser,'admin01');open_order(admin,code)
  admin.get_by_label('Alasan jika bukti ditolak',exact=True).fill('Foto bukti perlu diperjelas untuk pengujian ulang.')
  admin.get_by_role('button',name='Tolak bukti pembayaran',exact=True).click()
  expect(admin.get_by_role('dialog').get_by_text('Bukti ditolak. Pesanan masih aktif.',exact=True)).to_be_visible()
  guest.bring_to_front()
  expect(guest.get_by_text('Perbaiki bukti pembayaran',exact=True)).to_be_visible(timeout=20000)
  guest.get_by_label('Nomor referensi transaksi',exact=True).fill(REF)
  upload(guest,'Bukti pembayaran')
  guest.get_by_role('checkbox',name=re.compile('Saya sudah membayar sesuai tagihan')).check()
  guest.get_by_role('button',name='Kirim ulang bukti ↗',exact=True).click()
  expect(guest.get_by_text('Bukti pembayaran sedang diperiksa',exact=True)).to_be_visible(timeout=20000)
  admin.bring_to_front()
  expect(admin.get_by_label('Ketik ulang nomor referensi',exact=True)).to_be_visible(timeout=20000)
  admin.get_by_label('Ketik ulang nomor referensi',exact=True).fill(REF)
  admin.get_by_role('checkbox',name=re.compile('Nominal dan referensi sudah cocok')).check()
  admin.get_by_role('button',name='Setujui pembayaran',exact=True).click()
  expect(admin.get_by_role('dialog').get_by_text('Pembayaran diverifikasi.',exact=True)).to_be_visible(timeout=20000)
  guest.bring_to_front()
  expect(guest.get_by_text('Pembayaran terverifikasi',exact=True)).to_be_visible(timeout=20000)
  assert guest.locator('.tracking-token').inner_text().strip()==code
  log('PASS: cross-session polling, rejection, same-reference retry, manual verification')
  admin.bring_to_front();upload(admin,'Foto penerimaan')
  admin.get_by_role('button',name='Barang sudah diterima',exact=True).click()
  expect(admin.get_by_role('combobox',name='Teknisi cabang',exact=True)).to_be_visible(timeout=15000)
  option=admin.get_by_role('combobox',name='Teknisi cabang',exact=True).locator('option').filter(has_text='Teknisi Cabang 01').first.get_attribute('value')
  admin.get_by_role('combobox',name='Teknisi cabang',exact=True).select_option(option)
  admin.get_by_role('button',name='Simpan penugasan',exact=True).click()
  tc,tech=login(browser,'teknisi01');open_order(tech,code)
  upload(tech,'Foto sebelum');tech.get_by_role('button',name='Mulai pengerjaan ↗',exact=True).click()
  expect(tech.get_by_label('Foto sesudah',exact=True)).to_be_visible(timeout=15000)
  upload(tech,'Foto sesudah');tech.get_by_label('Catatan hasil',exact=True).fill('Treatment demo sudah selesai dan siap diperiksa.')
  tech.get_by_role('button',name='Kirim hasil ke QC ↗',exact=True).click()
  qc_ctx,qc=login(browser,'qc01');open_order(qc,code)
  qc.get_by_label('Hasil pemeriksaan / instruksi rework',exact=True).fill('Hasil bersih sesuai standar pada pengujian browser.')
  qc.get_by_role('button',name='Lulus QC',exact=True).click()
  expect(qc.locator('.order-detail-status .pill')).to_have_text('Siap diantar',timeout=15000)
  admin.bring_to_front()
  courier_id=admin.get_by_role('combobox',name='Kurir cabang',exact=True).locator('option').filter(has_text='Kurir Cabang 01').first.get_attribute('value',timeout=20000)
  admin.get_by_role('combobox',name='Kurir cabang',exact=True).select_option(courier_id)
  admin.get_by_role('button',name='Simpan penugasan',exact=True).click()
  courier_ctx,courier=login(browser,'kurir01');open_order(courier,code)
  courier.get_by_role('button',name='Mulai tugas ↗',exact=True).click()
  upload(courier,'Foto bukti serah terima')
  courier.get_by_label('Lokasi barang dititipkan (opsional)',exact=True).fill('Di rak samping pintu depan.')
  courier.get_by_role('button',name='Selesaikan dengan bukti ↗',exact=True).click()
  guest.bring_to_front();expect(guest.locator('.order-heading .pill')).to_have_text('Pesanan selesai',timeout=20000)
  proof=guest.get_by_role('region',name='Foto bukti pengantaran')
  expect(proof).to_contain_text('Di rak samping pintu depan.')
  proof.locator('img').scroll_into_view_if_needed()
  assert guest.request.get(proof.locator('img').get_attribute('src')).status==200
  guest.wait_for_function("document.querySelector('.delivery-proof img').naturalWidth > 0")
  log('PASS: reception, before/after, QC, courier return photo and note shown to customer')

  assessment=ctx.new_page();assessment.on('pageerror',lambda e:errors.append(str(e)))
  assessment.on('dialog',lambda dialog:dialog.accept())
  assessment.goto(BASE+'/book');wire(assessment)
  expect(assessment.locator('#f-service option:checked')).to_have_text(re.compile('^Deep Clean'))
  assessment.get_by_label('Nama lengkap *').fill('Customer Penilaian Demo')
  assessment.get_by_label('WhatsApp / telepon *').fill('081234567891')
  assessment.get_by_label('Jenis barang *').fill('Sneakers putih dua pasang')
  assessment.locator('input[value="DROPOFF"]').check()
  assessment.get_by_label('Jumlah barang (1–20) *').fill('2')
  expect(assessment.locator('.v3610-condition-card')).to_have_count(2)
  for number in [1,2]:
   assessment.get_by_label(f'Foto kondisi barang {number}',exact=True).set_input_files(str(PHOTO))
   expect(assessment.locator(f'.v3610-condition-photos img[alt="Foto kondisi barang {number}"]')).to_be_visible()
  assessment.get_by_role('button',name='Kirim untuk penilaian ↗',exact=True).click()
  assessment.wait_for_url(re.compile('/track/PURO-'),timeout=20000)
  assessment_code=assessment.locator('.tracking-token').inner_text().strip()
  expect(assessment.locator('.order-heading .pill')).to_have_text('Menunggu penilaian')
  admin.goto(BASE+'/staff');open_order(admin,assessment_code,'Deep Clean')
  expect(admin.get_by_role('heading',name='Foto & kondisi saat booking',exact=True)).to_be_visible()
  admin.get_by_label('Harga barang 1 (rupiah)',exact=True).fill('35000')
  admin.get_by_label('Harga barang 2 (rupiah)',exact=True).fill('45000')
  admin.get_by_label('Hasil penilaian (dibaca customer)',exact=True).fill('Harga per pasang sesuai kondisi barang demo.')
  admin.get_by_role('button',name='Kirim penawaran v1 ↗',exact=True).click()
  assessment.bring_to_front()
  assessment.get_by_role('button',name='Setujui harga & lanjut bayar ↗',exact=True).click(timeout=20000)
  assessment.wait_for_url(BASE+'/book/'+assessment_code+'/checkout')
  expect(assessment.locator('.v3610-offer-line.total')).to_contain_text('Rp80.000')
  expect(assessment.locator('.checkout-code .tracking-token')).to_have_text(assessment_code)
  log('PASS: two-item assessment photos, per-item offer and checkout with the same tracking code')

  rc,root=login(browser,'superadmin')
  expect(root.locator('.staff-navigation a.active')).to_have_css('color','rgb(32, 96, 56)')
  root.screenshot(path=str(OUT/'app-staff.png'))
  editors={
   'catalog':('.v3610-photo-card button[aria-label^="Ganti gambar"]','Gambar & harga.'),
   'branches':('.branch-manage-row','Edit cabang'),
   'accounts':('.account-list-row button[aria-label^="Edit "]','Perbarui data'),
   'promos':('.team-person button[aria-label^="Edit promo"]','Edit promo'),
  }
  for tab in ['catalog','branches','qris','accounts','permissions','promos','reports','knowledge']:
   response=root.goto(BASE+'/staff/manage/'+tab);assert response.status==200,(tab,response.status)
   no_overflow(root,'desktop-'+tab)
   if tab in editors:
    control,heading=editors[tab]
    root.locator(control).first.click()
    expect(root.get_by_role('heading',name=heading,exact=True)).to_be_visible()

  root.goto(BASE+'/staff/manage/catalog')
  suffix=str(time.time_ns())[-10:]
  category='Kategori Browser '+suffix
  service='Treatment Browser '+suffix
  root.get_by_role('button',name='＋ Tambah kategori',exact=True).click()
  root.get_by_label('Nama kategori',exact=True).fill(category)
  root.get_by_role('button',name='Tambah kategori',exact=True).click()
  expect(root.get_by_role('combobox',name='Kategori',exact=True).locator('option:checked')).to_have_text(category)
  root.get_by_label('Kode layanan',exact=True).fill('browser-'+suffix)
  root.get_by_label('Nama layanan',exact=True).fill(service)
  root.get_by_label('Deskripsi',exact=True).fill('Treatment baru dari pengujian aplikasi demo.')
  root.get_by_label('Harga minimum (rupiah)',exact=True).fill('25000')
  root.get_by_label('Harga maksimum (sama jika tetap)',exact=True).fill('25000')
  root.get_by_role('button',name='Buat data ↗',exact=True).click()
  row=root.locator('.v3610-photo-card').filter(has_text=service)
  expect(row).to_be_visible()
  row.get_by_role('button',name='Atur harga pembayaran '+service,exact=True).click()
  root.get_by_label('Harga minimum (Rp)',exact=True).fill('27000')
  root.get_by_label('Harga maksimum (Rp)',exact=True).fill('27000')
  root.get_by_role('button',name='Simpan gambar & harga ↗',exact=True).click()
  expect(row).to_contain_text('Rp27.000')
  row.get_by_role('button',name='Hapus gambar',exact=True).click()
  expect(row.locator('.v3610-photo-thumb img')).to_have_count(0)
  row.get_by_role('button',name='Contoh bawaan',exact=True).click()
  expect(row.locator('img')).to_be_visible()
  row.get_by_role('button',name='Hapus layanan '+service,exact=True).click()
  expect(row).to_have_count(0)

  root.goto(BASE+'/staff/manage/qris')
  expect(root.locator('.qris-preview img')).to_have_count(1)
  group='QRIS Browser '+suffix
  root.get_by_label('Nama grup QRIS',exact=True).fill(group)
  upload(root,'Gambar QRIS baru (opsional)')
  root.get_by_role('button',name='Tambah grup QRIS ↗',exact=True).click()
  expect(root.get_by_role('combobox',name='Grup QRIS',exact=True).locator('option:checked')).to_have_text(group)
  expect(root.locator('.qris-preview img')).to_have_count(1)
  root.goto(BASE+'/staff/manage/accounts')
  filters=root.get_by_role('region',name='Filter akun staf')
  filters.get_by_role('combobox',name='Cabang',exact=True).select_option(label='Cabang 02')
  filters.get_by_role('combobox',name='Peran',exact=True).select_option(label='Teknisi')
  expect(root.locator('.account-list-row')).to_have_count(1)
  expect(root.locator('.account-list-row').first).to_contain_text('@teknisi02')

  root.goto(BASE+'/staff/manage/promos')
  code_promo='E2EPROMO'+suffix
  root.get_by_label('Kode promo',exact=True).fill(code_promo)
  root.get_by_role('button',name='Simpan promo global ↗',exact=True).click()
  promo=root.locator('.team-person').filter(has_text=code_promo)
  promo.get_by_role('button',name=re.compile('Arsipkan')).click()
  expect(promo).to_have_count(0)
  root.get_by_role('button',name='Arsip promo',exact=True).click()
  expect(root.get_by_text(code_promo,exact=True)).to_be_visible()

  admin.goto(BASE+'/staff/manage/accounts')
  admin_filters=admin.get_by_role('region',name='Filter akun staf')
  expect(admin_filters.get_by_role('combobox',name='Cabang',exact=True)).to_have_count(0)
  admin_filters.get_by_role('combobox',name='Urutkan',exact=True).select_option('username')
  expect(admin.locator('.account-list-row').first).to_contain_text('@admin01')
  admin_filters.get_by_role('combobox',name='Peran',exact=True).select_option(label='Kurir')
  expect(admin.locator('.account-list-row')).to_have_count(1)
  for page,label in [(root,'superadmin'),(admin,'admin-cabang')]:
   page.goto(BASE+'/staff/manage/reports')
   page.get_by_role('button',name='7 hari',exact=True).click()
   expect(page.get_by_role('button',name='7 hari',exact=True)).to_have_attribute('aria-pressed','true')
   expect(page.locator('.weekly-column')).to_have_count(7)
   if label=='superadmin':
    page.get_by_role('button',name='Cabang 01',exact=True).click()
    expect(page.get_by_role('button',name='Cabang 01',exact=True)).to_have_attribute('aria-pressed','true')
   with page.expect_download() as download:
    page.get_by_role('link',name='Unduh PDF ↓',exact=True).click()
   pdf=OUT/f'laporan-{label}.pdf'
   download.value.save_as(str(pdf))
   assert pdf.read_bytes().startswith(b'%PDF-')
  log('PASS: add category and QRIS, remove service, role/branch filters, promo archive, both PDF downloads')
  bc,bad=login(browser,'admin02');assert bad.goto(BASE+'/staff/orders/'+str(courier.url).rsplit('/',1)[-1]).status==403
  log('PASS: other branch cannot open order')
  for width in [360,390,768]:
   mc=context(browser,viewport={'width':width,'height':844});mobile=mc.new_page();mobile.on('pageerror',lambda e:errors.append(str(e)))
   for url,label in [('/','home'),('/layanan','services'),('/book?service=2','booking'),('/book/'+assessment_code+'/checkout','checkout'),('/lokasi','branches'),('/panduan','guide'),('/staff/login','login'),('/track/'+code,'tracking')]:
    response=mobile.goto(BASE+url);assert response.status==200,(width,url,response.status);wire(mobile);no_overflow(mobile,f'{width}-{label}')
    if width==390 and label in ['home','services','booking','tracking']:mobile.screenshot(path=str(OUT/f'02-mobile-{label}.png'),)
   if width==360:
    mobile.get_by_role('link',name='Cek status',exact=True).click()
    expect(mobile.get_by_role('dialog',name='Status pesanan.',exact=True)).to_be_visible()
    no_overflow(mobile,'360-track-modal')
    mobile.keyboard.press('Escape')
    expect(mobile.get_by_role('dialog',name='Status pesanan.',exact=True)).not_to_be_visible()
    mobile.get_by_role('button',name='Buka Asisten PURO',exact=True).click()
    mobile.get_by_label('Tulis pertanyaan',exact=True).fill('alo');mobile.get_by_role('button',name='Kirim pertanyaan',exact=True).click()
    expect(mobile.locator('.puro-assistant-message.assistant').last).to_contain_text('Halo!')
    mobile.get_by_label('Tulis pertanyaan',exact=True).fill('Saya perlu admin untuk pembayaran');mobile.get_by_role('button',name='Kirim pertanyaan',exact=True).click()
    expect(mobile.locator('.puro-assistant-handoff').last).to_be_visible();no_overflow(mobile,'360-chat')
    mobile.screenshot(path=str(OUT/'03-mobile-chat.png'))
    mobile.reload();wire(mobile);mobile.get_by_role('button',name='Buka Asisten PURO',exact=True).click();expect(mobile.locator('.puro-assistant-handoff').last).to_be_visible()
   mc.close()
  log('PASS: mobile 360/390/768px, chatbot greeting/fallback and browser history')
  root.set_viewport_size({'width':390,'height':844})
  for tab in ['catalog','branches','qris','accounts','permissions','promos','reports','knowledge']:
   assert root.goto(BASE+'/staff/manage/'+tab).status==200;no_overflow(root,'mobile-admin-'+tab)
  root.goto(BASE+'/staff')
  root.get_by_role('button',name='Menu dashboard staf').click()
  expect(root.locator('#staff-nav')).to_be_visible()
  root.get_by_role('button',name='Keluar ↗',exact=True).click()
  root.wait_for_url(BASE+'/staff/login')
  expect(root.get_by_role('heading',name='Akses staf.')).to_be_visible()
  log('PASS: mobile staff menu and logout returns to login')
  assert not errors,errors
  assert not failures,failures
  log('PASS: no JavaScript exceptions, no app HTTP 5xx, no horizontal page overflow')
  (OUT/'result.json').write_text(json.dumps({'checks':checks,'javascript_errors':errors,'http_5xx':failures,'tracking_code':code},indent=2))
  browser.close()
if __name__=='__main__':run()
