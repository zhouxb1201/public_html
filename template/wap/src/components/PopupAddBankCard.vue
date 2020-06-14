<template>
  <van-popup
    v-model="value"
    position="bottom"
    :close-on-click-overlay="false"
    class="popup-bank-card"
  >
    <van-nav-bar title="添加银行卡" left-text="返回" left-arrow fixed :z-index="999" @click-left="close" />
    <CellBankAutoGroup :loading="isLoading" :params="params" @save="save" />
    <PopupBankCardSms
      v-model="showBankCardSms"
      type="signing"
      :params="params"
      @success="signingSuccess"
      @close="signingClose"
    />
  </van-popup>
</template>

<script>
import { NavBar } from "vant";
import { SET_ASSETACCOUNT } from "@/api/property";
import PopupBankCardSms from "@/components/PopupBankCardSms";
import CellBankAutoGroup from "@/pages/property/account/component/CellBankAutoGroup";
export default {
  data() {
    return {
      params: {},
      isLoading: false,

      showBankCardSms: false
    };
  },
  props: {
    value: Boolean
  },
  methods: {
    close() {
      this.$emit("input", false);
    },
    signingClose() {
      this.isLoading = false;
    },
    //签约成功
    signingSuccess() {
      this.close();
      this.$emit("success");
    },
    save(params) {
      params.type = 1;
      this.isLoading = true;
      // console.log(params);
      // return;
      SET_ASSETACCOUNT("add", params)
        .then(({ code, data, message }) => {
          if (code == 1) {
            if (data.thpinfo) {
              this.params.thpinfo = data.thpinfo;
              this.showBankCardSms = true;
            } else {
              this.$Toast("获取短信验证失败");
              this.isLoading = false;
            }
          } else {
            this.$Toast.success(message);
          }
        })
        .catch(() => {
          this.isLoading = false;
        });
    }
  },
  beforeDestroy() {
    this.close();
  },
  components: {
    [NavBar.name]: NavBar,
    CellBankAutoGroup,
    PopupBankCardSms
  }
};
</script>

<style scoped>
.popup-bank-card {
  height: 100%;
  padding-top: 46px;
  border-radius: 0;
}
</style>