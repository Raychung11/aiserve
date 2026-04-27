<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card-box">
<form action="<?= APP_URL ?>/properties" method="POST">
<input type="hidden" name="_token" value="<?= Auth::csrfToken() ?>">
<input type="hidden" name="action" value="<?= $property ? 'edit' : 'create' ?>">
<?php if($property): ?><input type="hidden" name="id" value="<?= $property['id'] ?>"><?php endif; ?>

<h6 class="fw-semibold mb-3 pb-2 border-bottom">Basic Information</h6>
<div class="row g-3 mb-4">
  <div class="col-12"><label class="form-label fw-semibold">Property Name / Unit</label>
    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($property['name']??'') ?>" placeholder="e.g. Verve Suites KL South Unit 12A" required></div>
  <div class="col-12"><label class="form-label fw-semibold">Address</label>
    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($property['address']??'') ?>" required></div>
  <div class="col-md-4"><label class="form-label fw-semibold">City</label>
    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($property['city']??'') ?>" required></div>
  <div class="col-md-4"><label class="form-label fw-semibold">State</label>
    <select name="state" class="form-select" required>
      <option value="">Select</option>
      <?php foreach(['Kuala Lumpur','Selangor','Penang','Johor','Sabah','Sarawak','Melaka','Negeri Sembilan','Perak','Kedah','Kelantan','Terengganu','Pahang','Perlis','Putrajaya','Labuan'] as $s): ?>
      <option value="<?=$s?>" <?= ($property['state']??'')===$s?'selected':'' ?>><?=$s?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="col-md-4"><label class="form-label fw-semibold">Postcode</label>
    <input type="text" name="postcode" class="form-control" value="<?= htmlspecialchars($property['postcode']??'') ?>" maxlength="10" required></div>
</div>

<h6 class="fw-semibold mb-3 pb-2 border-bottom">Property Specs</h6>
<div class="row g-3 mb-4">
  <div class="col-md-4"><label class="form-label fw-semibold">Property Type</label>
    <select name="property_type" class="form-select" required>
      <?php foreach(['condo'=>'Condominium','serviced_apartment'=>'Serviced Apartment','landed'=>'Landed','commercial'=>'Commercial','soho'=>'SOHO','sofo'=>'SOFO'] as $v=>$l): ?>
      <option value="<?=$v?>" <?= ($property['property_type']??'condo')===$v?'selected':'' ?>><?=$l?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="col-md-4"><label class="form-label fw-semibold">Bedrooms</label>
    <input type="number" name="bedrooms" class="form-control" value="<?= $property['bedrooms']??1 ?>" min="0" required></div>
  <div class="col-md-4"><label class="form-label fw-semibold">Bathrooms</label>
    <input type="number" name="bathrooms" class="form-control" value="<?= $property['bathrooms']??1 ?>" min="0" required></div>
  <div class="col-md-6"><label class="form-label fw-semibold">Building / Strata Name</label>
    <input type="text" name="strata_building" class="form-control" value="<?= htmlspecialchars($property['strata_building']??'') ?>"></div>
  <div class="col-md-3"><label class="form-label fw-semibold">Area (sqft)</label>
    <input type="number" name="area_sqft" class="form-control" value="<?= $property['area_sqft']??'' ?>" step="1" min="0"></div>
  <div class="col-md-3 d-flex align-items-end pb-1">
    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_strata" id="is_strata" <?= !empty($property['is_strata'])||!$property?'checked':'' ?>>
    <label class="form-check-label" for="is_strata">Strata Title</label></div>
  </div>
</div>

<h6 class="fw-semibold mb-3 pb-2 border-bottom">Strategy &amp; Owner</h6>
<div class="row g-3 mb-4">
  <div class="col-md-4"><label class="form-label fw-semibold">Strategy Mode</label>
    <select name="strategy_mode" class="form-select" required>
      <?php foreach(['STR'=>'STR (Short-Term)','MID_TERM'=>'Mid-Term (30–90d)','SUBLET'=>'Room Sublet','CORPORATE'=>'Corporate Lease'] as $v=>$l): ?>
      <option value="<?=$v?>" <?= ($property['strategy_mode']??'STR')===$v?'selected':'' ?>><?=$l?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="col-md-4"><label class="form-label fw-semibold">Listing Status</label>
    <select name="listing_status" class="form-select">
      <?php foreach(['active','inactive','pending','maintenance'] as $s): ?>
      <option value="<?=$s?>" <?= ($property['listing_status']??'pending')===$s?'selected':'' ?>><?=ucfirst($s)?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="col-md-4"><label class="form-label fw-semibold">Assigned Agent</label>
    <select name="agent_id" class="form-select">
      <option value="">No agent</option>
      <?php foreach($agents as $a): ?>
      <option value="<?=$a['id']?>" <?= ($property['agent_id']??'')==$a['id']?'selected':'' ?>><?= htmlspecialchars($a['name']) ?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="col-md-8">
    <label class="form-label fw-semibold">Owner
      <a href="<?= APP_URL ?>/owners?action=create" class="ms-2 text-primary" style="font-size:.75rem;" target="_blank"><i class="bi bi-plus-circle me-1"></i>New Owner</a>
    </label>
    <select name="owner_id" class="form-select">
      <option value="">No owner assigned</option>
      <?php foreach($owners as $ow): ?>
      <option value="<?=$ow['id']?>" <?= ($property['owner_id']??'')==$ow['id']?'selected':'' ?>>
        <?= htmlspecialchars($ow['name']) ?><?= $ow['phone']?' — '.$ow['phone']:'' ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4"><label class="form-label fw-semibold">Monthly Target (RM)</label>
    <input type="number" name="monthly_target" class="form-control" value="<?= $property['monthly_target']??0 ?>" min="0" step="50"></div>
  <div class="col-md-6"><label class="form-label fw-semibold">Airbnb URL</label>
    <input type="url" name="airbnb_url" class="form-control" value="<?= htmlspecialchars($property['airbnb_url']??'') ?>"></div>
</div>

<div class="d-flex gap-2 justify-content-end">
  <a href="<?= APP_URL ?>/properties" class="btn btn-outline-secondary">Cancel</a>
  <button type="submit" class="btn btn-primary px-4"><?= $property ? 'Save Changes' : 'Add Property' ?></button>
</div>
</form>
</div>
</div>
</div>
