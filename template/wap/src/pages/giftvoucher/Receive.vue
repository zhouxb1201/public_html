<template>
  <Layout ref="load" class="giftvoucher-receive bg-ff454e">
    <Navbar />
    <ReceiveHeadInfo :detail="detail" />
    <div class="apply-store-wrap">
      <h3>适用门店</h3>
      <div class="apply-store-main">
        <List v-model="loading" :finished="finished" :error.sync="error" @load="loadList">
          <div class="store-li" v-for="(item,index) in list" :key="index">
            <section class="store-li-theme">
              <span>{{item.store_name}}</span>
              <span>{{item.distance | distance}}</span>
            </section>
            <van-cell :title="item.address" icon="location-o" />
          </div>
        </List>
      </div>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import ReceiveHeadInfo from "./component/ReceiveHeadInfo";
import {
  GET_GIFTVOUCHERDETAILRECEIVE,
  GET_GIFTVOUCHERSTORE
} from "@/api/giftvoucher";
import { list } from "@/mixins";
export default sfc({
  name: "giftvoucher-receive",
  data() {
    return {
      detail: {},
      params: {
        page_index: 1,
        page_size: 20
      }
    };
  },
  mixins: [list],
  filters: {
    distance(value) {
      return value * 1000 + "m";
    }
  },
  mounted() {
    this.loadData();
    this.$store
      .dispatch("getBMapLocation")
      .then(({ location }) => {
        this.params.lng = location.lng;
        this.params.lat = location.lat;
        this.loadList("init");
      })
      .catch(error => {
        this.$Toast({ message: error + "将显示所有门店", duration: 3000 });
        this.loadList("init");
      });
  },
  methods: {
    loadData() {
      const $this = this;
      GET_GIFTVOUCHERDETAILRECEIVE($this.$route.params.giftvoucherid)
        .then(({ data }) => {
          $this.detail = data;
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      $this.params.gift_voucher_id = $this.$route.params.giftvoucherid;
      GET_GIFTVOUCHERSTORE($this.params)
        .then(({ data }) => {
          let list = data.store_list ? data.store_list : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  beforeDestroy() {
    var iframes = document.getElementsByTagName("iframe")[0];
    iframes && iframes.remove();
  },
  components: {
    ReceiveHeadInfo
  }
});
</script>

<style scoped>
.bg-ff454e {
  background: #ff454e;
}
.apply-store-wrap {
  position: relative;
  overflow: hidden;
  text-align: center;
}
.apply-store-wrap h3 {
  font-weight: normal;
  font-size: 14px;
  color: #fff;
}
.apply-store-main {
  position: relative;
  overflow-x: hidden;
  overflow-y: auto;
  max-height: 300px;
  margin: 10px 15px;
  background-color: #fff;
  border-radius: 10px;
  padding: 12px 10px 0px 10px;
}
.store-li {
  padding-bottom: 10px;
}
.store-li >>> .van-cell {
  padding: 4px 0;
}
.store-li >>> .van-cell__title {
  text-align: left;
  color: #999;
}
.store-li-theme {
  display: flex;
  justify-content: space-between;
  color: #ff454e;
}
.store-li-theme span:first-child {
  font-weight: 800;
}
.store-li-theme span:last-child {
  font-size: 12px;
}
</style>


