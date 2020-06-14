<template>
  <Layout ref="load" class="bonus-certificate bg-f8">
    <Navbar />
    <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTab" />
    <div v-if="!showEmpty">
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
      <PopupWechatName :show="show" @cancel="onCancel" @sure="onSure" />
    </div>
    <Empty :showFoot="false" :message="message" pageType="fail" v-else />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_USERWECHAT } from "@/api/credential";
import { clipboard } from "@/mixins";
import { _decode } from "@/utils/base64";
import PopupWechatName from "@/components/PopupWechatName";
import HeadTab from "@/components/HeadTab";
import Empty from "@/components/Empty";
export default sfc({
  name: "bonus-certificate",
  data() {
    return {
      show: false,
      img_path: "",
      cred_no: "",
      tab_active: 0,
      tabs: [
        {
          name: "团队队长",
          role_type: 1
        },
        {
          name: "区域代理",
          role_type: 2
        },
        {
          name: "全球股东",
          role_type: 3
        }
      ],
      wchat_name: "",
      message: "未申请团队队长",
      showEmpty: false
    };
  },
  mixins: [clipboard],
  computed: {
    roleType() {
      return JSON.parse(_decode(this.$route.query.roleType));
    }
  },
  mounted() {
    if (this.$store.state.config.addons.credential) {
      this.$refs.load.success();
      this.loadData();
    } else {
      this.$refs.load.fail({
        errorType: "fail",
        errorText: "未开启授权证书应用",
        showFoot: false
      });
    }
  },
  methods: {
    loadData() {
      GET_USERWECHAT().then(res => {
        if (res.code > 0) {
          this.wchat_name = res.wchat_name;
          this.getCredential(1);
        } else if (res.code == 0) {
          this.show = true;
        }
      });
    },
    onTab(index) {
      const that = this;
      const role_type = that.tabs[index].role_type;
      that.getCredential(role_type);
    },
    getCredential(role_type) {
      const that = this;
      if (that.roleType.indexOf(role_type) == -1) {
        let message = "";
        if (role_type == 1) {
          message = "未申请团队队长";
        } else if (role_type == 2) {
          message = "未申请区域代理";
        } else if (role_type == 3) {
          message = "未申请全球股东";
        }
        that.message = message;
        that.showEmpty = true;
        return false;
      } else {
        that.showEmpty = false;
      }
      that.$store
        .dispatch("getCredential", {
          type: 2,
          role_type: role_type,
          wchat_name: that.wchat_name
        })
        .then(data => {
          if (data.img_path.substring(0, 4) == "http") {
            that.img_path = data.img_path;
          } else {
            that.img_path = `${that.$store.state.domain}/${data.img_path}`;
          }
          that.cred_no = data.cred_no;
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
      this.wchat_name = val;
      this.getCredential(1);
    }
  },
  components: {
    PopupWechatName,
    HeadTab,
    Empty
  }
});
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