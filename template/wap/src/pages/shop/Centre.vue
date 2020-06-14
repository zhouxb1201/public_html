<template>
  <Layout ref="load" class="shop-centre">
    <Navbar />
    <HeadBanner :src="$BASEIMGPATH + 'shop-join-adv.png'" />
    <van-cell class="cell">
      <div class="foot-btn-group">
        <van-button round size="normal" block type="danger" @click="onClick">{{stateText}}</van-button>
      </div>
    </van-cell>
    <van-tabs v-model="tab_active">
      <van-tab :title="item.title" v-for="(item,index) in tabs" :key="index">
        <div class="content">
          <div v-if="item.content" v-html="item.content"></div>
          <div class="empty" v-else>暂无协议</div>
        </div>
      </van-tab>
    </van-tabs>
    <Protocol
      v-model="showProtocol"
      :title="joinInfo.title"
      :content="joinInfo.content"
      v-if="$store.state.shop.applyState == 'apply'"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import HeadBanner from "@/components/HeadBanner";
import Protocol from "./component/Protocol";
import { GET_SHOPAPPLYPROTOCOL } from "@/api/shop";
export default sfc({
  name: "shop-centre",
  data() {
    return {
      tab_active: 0,
      tabs: [],
      showProtocol: false,
      joinInfo: {}
    };
  },
  computed: {
    stateText() {
      const state = this.$store.state.shop.applyState;
      return state == "apply" ? "申请入驻" : "查看进度";
    }
  },
  mounted() {
    if (this.$store.state.config.addons.shop) {
      this.loadData();
    } else {
      this.$refs.load.fail({ errorText: "未开启店铺应用", showFoot: false });
    }
  },
  methods: {
    loadData() {
      this.$store
        .dispatch("getShopApplyState")
        .then(() => {
          GET_SHOPAPPLYPROTOCOL()
            .then(({ data }) => {
              this.tabs = data.shop_protocol.filter((e, i) => i < 4);
              this.joinInfo = data.shop_protocol[4];
              this.$refs.load.success();
            })
            .catch(() => {
              this.$refs.load.fail();
            });
        })
        .catch(error => {
          if (error) {
            this.$refs.load.fail({
              errorText: "未开启店铺应用",
              showFoot: false
            });
          } else {
            this.$refs.load.fail();
          }
        });
    },
    onClick() {
      if (this.$store.state.shop.applyState == "apply") {
        this.showProtocol = true;
      } else {
        this.$router.push("/shop/result");
      }
    }
  },
  components: {
    HeadBanner,
    Protocol
  }
});
</script>

<style scoped>
.cell {
  margin: 10px 0;
}

.btn-group {
  margin: 10px 20px;
}

.content {
  background: #ffffff;
  padding: 10px;
}
</style>


