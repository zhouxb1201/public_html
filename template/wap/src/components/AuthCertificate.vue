<template>
  <div class="certificate bg-f8">
    <div>
      <van-cell-group>
        <div class="cert-wrp">
          <img :src="img_path" :onerror="$ERRORPIC.noGoods" />
        </div>
        <van-cell>
          <div class="text-center fs-12 text-regular">(长按图片保存证书分享)</div>
        </van-cell>
      </van-cell-group>
      <van-cell-group class="cell-group">
        <van-field center readonly :value="cred_no" label="证书编号">
          <van-button
            class="a-copy"
            slot="button"
            size="mini"
            type="danger"
            :data-clipboard-text="cred_no"
            @click="onCopy"
          >复制</van-button>
        </van-field>
      </van-cell-group>
      <van-cell class="cell-group">
        <div>证书小提示：</div>
        <div>
          <p>授权证书用于展示身份，会员可扫码证书上面的二维码查看证书是否真伪。</p>
        </div>
      </van-cell>
    </div>
    <PopupWechatName :show="show" @cancel="onCancel" @sure="onSure" />
  </div>
</template>

<script>
import { GET_USERWECHAT } from "@/api/credential";
import { clipboard } from "@/mixins";
import PopupWechatName from "./PopupWechatName";
export default {
  data() {
    return {
      show: false,
      img_path: "",
      cred_no: ""
    };
  },
  props: {
    type: [String, Number]
  },
  mixins: [clipboard],
  computed: {},
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      GET_USERWECHAT().then(res => {
        if (res.code > 0) {
          this.getCredential(res.wchat_name);
        } else if (res.code == 0) {
          this.show = true;
        }
      });
    },
    getCredential(wchat_name) {
      const that = this;
      that.$store
        .dispatch("getCredential", {
          type: that.type,
          wchat_name: wchat_name
        })
        .then(data => {
          if (data.img_path.substring(0, 4) == "http") {
            that.img_path = data.img_path;
          } else {
            that.img_path = `${that.$store.state.domain}/${data.img_path}`;
          }
          if(data.cred_no){
            that.cred_no = data.cred_no;
            that.show = false;
          }else{
            that.$Toast.fail('生成证书出错');
          }
        });
    },
    onCancel() {
      this.show = false;
      this.$router.back();
    },
    onSure(val) {
      if (!val) {
        this.$Toast("请输入您的微信号！");
        return false;
      }
      this.getCredential(val);
    }
  },
  components: {
    PopupWechatName
  }
};
</script>

<style  scoped>
.cell-group {
  margin: 10px 0;
}

.cert-wrp img {
  width: 100%;
  display: block;
}
</style>