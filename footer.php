<?php /* footer.php */ ?>
<style>
  .footer{
    margin-top:48px;
    background:
      linear-gradient(180deg, rgba(37,99,235,.08), rgba(15,23,42,.0) 140px),
      #0f1e3a; /* deep institutional blue */
    color:#e5e7eb;
    border-top:1px solid rgba(255,255,255,.10);
  }

  .footerWrap{
    padding:56px 0 32px;
  }

  .footerGrid{
    display:grid;
    grid-template-columns: 1.6fr 1fr 1fr 1.2fr;
    gap:30px;
    align-items:start;
  }

  /* Brand */
  .footerBrand{
    display:flex;
    flex-direction:column;
    gap:16px;
  }

  .footerBrandTop{
    display:flex;
    align-items:center;
    gap:14px;
  }

  .footerBrand img{
    height:60px;
    width:auto;
    display:block;
    background:#ffffff;
    padding:6px;
    border-radius:14px;
    box-shadow:
      0 8px 18px rgba(15,23,42,.35),
      inset 0 0 0 1px rgba(15,23,42,.06);
  }

  .footerBrand strong{
    font-size:16px;
    font-weight:900;
    letter-spacing:.25px;
    color:#ffffff;
  }

  .footerBrand small{
    display:block;
    margin-top:2px;
    font-size:12.5px;
    color:rgba(226,232,240,.75);
  }

  .footerBrand p{
    color:rgba(226,232,240,.82);
    font-size:13.8px;
    line-height:1.7;
    max-width:440px;
  }

  /* Columns */
  .footerCol h4{
    font-size:13px;
    font-weight:900;
    letter-spacing:.4px;
    color:#ffffff;
    margin-bottom:16px;
    text-transform:uppercase;
  }

  .footerLink{
    display:block;
    padding:10px 4px;
    color:rgba(226,232,240,.78);
    font-size:13.8px;
    transition: color .12s ease, transform .12s ease;
  }

  .footerLink:hover{
    color:#ffffff;
    transform: translateX(2px);
  }

  /* Contact blocks */
  .footerContact{
    display:flex;
    flex-direction:column;
    gap:12px;
  }

  .footerContactItem{
    padding:12px 14px;
    border-radius:14px;
    border:1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.05);
    font-size:13.8px;
    color:rgba(226,232,240,.85);
    line-height:1.6;
  }

  .footerContactItem b{
    display:block;
    margin-bottom:2px;
    font-weight:800;
    color:#ffffff;
  }

  .footerContactItem a{
    color:#ffffff;
    text-decoration:none;
  }

  .footerContactItem a:hover{
    text-decoration:underline;
  }

  /* Bottom */
  .footerBottom{
    border-top:1px solid rgba(255,255,255,.12);
    padding:18px 0;
    background: rgba(15,23,42,.35);
  }

  .footerBottomInner{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    color:rgba(226,232,240,.7);
    font-size:13px;
  }

  /* Responsive */
  @media (max-width: 980px){
    .footerGrid{
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 768px){
    .footerGrid{
      grid-template-columns: 1fr;
      gap:20px;
    }
  }

  @media (max-width: 560px){
    .footerWrap{padding:40px 0 26px}
    .footerGrid{
      grid-template-columns: 1fr;
      gap:22px;
    }
    .footerBrand img{height:56px}
    .footerBottomInner{
      justify-content:center;
      text-align:center;
    }
  }
</style>

<footer class="footer">
  <div class="container footerWrap">
    <div class="footerGrid">

      <!-- Brand -->
      <div class="footerBrand">
        <div class="footerBrandTop">
          <img src="spg_logo2.png" alt="SPG Logo" />
          <div>
            <strong>საქართველოს სტუდენტური პარლამენტი და მთავრობა</strong>
            <small>Student Platform & Official Updates</small>
          </div>
        </div>

        <p>
          სტუდენტური პლატფორმა ოფიციალური სიახლეებისთვის, ორგანიზაციული
          სტრუქტურისა და საზოგადოებრივი ჩართულობისთვის.
        </p>
      </div>

      <!-- Navigation -->
      <div class="footerCol">
        <h4>ნავიგაცია</h4>
        <a class="footerLink" href="<?=h(url('index#home'))?>">მთავარი</a>
        <a class="footerLink" href="<?=h(url('index#news'))?>">სიახლეები</a>
        <a class="footerLink" href="<?=h(url('index#about-history'))?>">ჩვენს შესახებ</a>
        <a class="footerLink" href="<?=h(url('index#contact'))?>">კონტაქტი</a>
      </div>

      <!-- Team -->
      <div class="footerCol">
        <h4>გუნდი</h4>
        <a class="footerLink" href="<?=h(url('pr-event'))?>">PR & Event</a>
        <a class="footerLink" href="<?=h(url('aparati'))?>">აპარატი</a>
        <a class="footerLink" href="<?=h(url('parlament'))?>">სტუდენტური პარლამენტი</a>
        <a class="footerLink" href="<?=h(url('gov'))?>">სტუდენტური მთავრობა</a>
      </div>

      <!-- Contact -->
      <div class="footerCol footerContact">
        <h4>კონტაქტი</h4>

        <div class="footerContactItem">
          <b>ტელეფონი</b>
          <a href="tel:+995591037047">+995 591 037 047</a>
        </div>

        <div class="footerContactItem">
          <b>ელფოსტა</b>
          <a href="mailto:info@spg.ge">info@spg.ge</a>
        </div>

        <div class="footerContactItem">
          <b>მისამართი</b>
          თბილისი, ჟიულ შარტავას 35–37
        </div>
      </div>

    </div>
  </div>

  <div class="footerBottom">
    <div class="container footerBottomInner">
      <span>© <?php echo date("Y"); ?> საქართველოს სტუდენტური პარლამენტი და მთავრობა. All rights reserved.</span>
      <span>Official Student Platform</span>
    </div>
  </div>
</footer>

</body>
</html>
