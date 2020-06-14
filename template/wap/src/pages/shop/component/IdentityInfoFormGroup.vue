<template>
  <van-cell-group title="身份验证" class="cell-group">
    <van-field label="身份证号" required v-model="form.contacts_card_no" placeholder="必填，请输入身份证号"/>
    <CellUploadImage
      label="手持身份证照"
      show-example
      :example-img="$BASEIMGPATH + 'identity-img-1.png'"
      v-model="form.contacts_card_electronic_1"
    />
    <CellUploadImage
      label="身份证正照"
      show-example
      :example-img="$BASEIMGPATH + 'identity-img-2.png'"
      v-model="form.contacts_card_electronic_2"
    />
    <CellUploadImage
      label="身份证反照"
      show-example
      :example-img="$BASEIMGPATH + 'identity-img-3.png'"
      v-model="form.contacts_card_electronic_3"
    />
  </van-cell-group>
</template>

<script>
import CellUploadImage from "./CellUploadImage";
export default {
  props: {
    form: Object
  },
  components: {
    CellUploadImage
  }
};
</script>
