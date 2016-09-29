<form class="form-horizontal well" method="post">
        
  <input type="hidden" name="spl-form[id]" value="contact" />
  <?php wp_crass_response_fields(); ?>

  <div class="row-fluid">
  
    <div class="span6">

      <div class="control-group">
        <label class="control-label" for="spl-form-message">Your Message <i class="icon icon-comment"></i></label>
        <div class="controls">
          <textarea id="spl-form-message" name="spl-form[message]" class="span12" rows="7"></textarea>
        </div>
      </div>
      
    </div><!-- /.span6 -->
    
    <div class="span6">

      <div class="control-group">
        <label class="control-label" for="spl-form-name">Your Name <i class="icon icon-user"></i></label>
        <div class="controls">
          <input type="text" id="spl-form-name" name="spl-form[name]" class="span12" placeholder="">
        </div>
      </div>
      
      <div class="control-group">
        <label class="control-label" for="spl-form-barcode">Library Card <i class="icon icon-barcode"></i></label>
        <div class="controls">
          <input type="text" id="spl-form-barcode" name="spl-form[barcode]" class="span12 required">
          <span class="help-block">Barcode beginning with <code>27413</code></span>
        </div>
      </div>
      
      <div class="control-group">
        <label class="control-label" for="spl-form-query-type">Question About <i class="icon icon-question-sign"></i></label>
        <div class="controls">
          <select id="spl-form-query-type" name="spl-form[query-type]" class="span12">
            <option value="library">Using the Library</option>
            <option value="account">My Account</option>
            <option value="website">The website</option>
            <option value="other">Other</option>
          </select>
        </div>
      </div>
      
    </div><!-- /.span6 -->

  </div><!-- /.row-fluid -->
  
  <div class="control-group">
    <label class="control-label">Contact Method <i class="icon icon-share"></i></label>
    <div class="controls">
      
      <ul class="nav nav-tabs" id="spl-form-tabs">
        <li class="active"><a href="#spl-form-tab-email">Email</a></li>
        <li><a href="#spl-form-tab-phone">Phone or Fax</a></li>
        <li><a href="#spl-form-tab-address">Mailing Address</a></li>
      </ul><!-- /.nav-tabs -->
       
      <div class="tab-content">
        <div class="tab-pane active" id="spl-form-tab-email">
          <p>
            <label for="spl-form-email">Email Address</label>
            <input type="text" id="spl-form-email" name="spl-form[email]" class="span5" placeholder="">
          </p>
        </div><!-- /.tab-pane -->

        <div class="tab-pane" id="spl-form-tab-phone">
          <p>
            <label for="spl-form-phone">Phone Number</label>
            <input type="text" id="spl-form-phone" name="spl-form[phone]" class="span5" placeholder="">
          </p>
          <p>
            <label for="spl-form-fax">Fax Number</label>
            <input type="text" id="spl-form-fax" name="spl-form[fax]" class="span5" placeholder="">
          </p>
        </div><!-- /.tab-pane -->

        <div class="tab-pane" id="spl-form-tab-address">
          <p>
            <label for="spl-form-street">Street Address</label>
            <textarea id="spl-form-street" name="spl-form[street]" class="span5" placeholder="" rows="2"></textarea>
          </p>
          <p>
            <label for="spl-form-city">City</label>
            <input type="text" id="spl-form-city" name="spl-form[city]" class="span5" placeholder="">
          </p>
          <p>
            <label for="spl-form-state">State</label>
            <input type="text" id="spl-form-state" name="spl-form[state]" class="span5" placeholder="" value="WA">
          </p>
          <p>
            <label for="spl-form-zip">Zip Code</label>
            <input type="text" id="spl-form-zip" name="spl-form[zip]" class="span5" placeholder="">
          </p>
        </div><!-- /.tab-pane -->
        
      </div><!-- /.tab-content -->
      
      <span class="help-block">Please enter your email address, phone number, or mailing address so we can respond to your inquiry.</span>
    
    </div><!-- /.controls -->
  </div> <!-- /.control-group -->
  
  <hr />
  
  <div class="control-group">
    <div class="controls">
      <button type="submit" class="btn btn-success"><i class="icon-white icon-envelope"></i> Send this message now</button>
    </div>
  </div>

</form>
