<template>
  <div class="verify bg-f8">
    <Navbar/>
    <div class="box">
      <div class="qr e-handle" @click="onScan">
        <van-icon name="scan" color="#666" size="12em"/>
      </div>
      <div class="text">点击上方图标进行扫码</div>
    </div>
    <van-cell-group class="flex-foot cell-group">
      <van-field v-model.trim="value" left-icon="idcard" placeholder="扫码没反应？试试手动输入" clearable>
        <van-button slot="button" size="mini" type="danger" @click="onConfirm">确定</van-button>
      </van-field>
    </van-cell-group>
  </div>
</template>

<script>
import { isIos } from "@/utils/util";
import { focusout } from "@/mixins";
export default {
  name: "verify",
  data() {
    return {
      value: ""
    };
  },
  mixins: [focusout],
  beforeRouteEnter(to, from, next) {
    if ("/clerk" + to.path !== global.location.pathname && isIos()) {
      // ios手机 刷新页面获取当前url
      location.assign("/clerk" + to.fullPath);
    } else {
      next();
    }
  },
  methods: {
    onScan() {
      this.$store.dispatch("scanQRCode").then(link => {
        if (link) {
          if (
            link.indexOf("http://") !== -1 ||
            link.indexOf("https://") !== -1
          ) {
            window.location.href = link;
          } else {
            const code = this.validCode(link);
            if (code) this.onPost(code);
          }
        } else {
          this.$Toast("扫码出错");
        }
      });
    },
    onConfirm() {
      const code = this.validCode(this.value);
      this.onPost(code);
    },
    onPost(code) {
      const codeType = this.getCodeType(code);
      switch (codeType) {
        case "A":
          this.$router.push({
            name: "verify-order",
            params: {
              code
            }
          });
          break;
        case "B":
          this.$router.push({
            name: "verify-cardvoucher",
            params: {
              code
            }
          });
          break;
        case "C":
          this.$router.push({
            name: "verify-gift",
            params: {
              code
            }
          });
          break;
      }
    },
    /**
     * 根据提货码判断类型
     * A ==> 订单
     * B ==> 卡券
     * C ==> 礼品券
     */
    getCodeType(code) {
      let type = "";
      if (code) {
        type = code.substr(0, 1);
      }
      return type;
    },
    // 验证提货码格式
    validCode(value) {
      var falg = /^[A-C][0-9]*$/.test(value);
      if (!falg) {
        this.$Toast("请输入正确提货码！");
        return false;
      }
      return value;
    }
  }
};
</script>

<style scoped>
.box {
  display: flex;
  justify-content: center;
  align-items: center;
  background: #fff;
  padding: 50px;
  flex-flow: column;
}

.box .text {
  margin-top: 20px;
  color: #666;
}

.flex-foot {
  position: fixed;
  width: 100%;
  bottom: 0;
  z-index: 99;
  padding: 10px 0;
}
</style>
