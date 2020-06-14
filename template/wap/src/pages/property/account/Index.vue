<template>
  <Layout ref="load" class="property-account bg-f8">
    <Navbar />
    <div class="list">
      <van-cell-group
        class="card-group-box"
        v-for="(item,index) in list"
        :key="index"
        @click="toDetail(item)"
      >
        <CellAccountItem class="cell" :item="item" />
      </van-cell-group>
    </div>
    <div class="fixed-foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onAdd">新增账户</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CellAccountItem from "@/components/CellAccountItem";
import { GET_ASSETACCOUNTLIST, DEL_ASSETACCOUNT } from "@/api/property";
import { _encode } from "@/utils/base64";
import { property } from "@/mixins";
export default sfc({
  name: "property-account",
  data() {
    return {
      list: []
    };
  },
  mixins: [property],
  activated() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_ASSETACCOUNTLIST()
        .then(({ data }) => {
          $this.list = $this.packageAccountList(data);
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    onAdd() {
      this.$router.push({
        name: "property-account-post",
        hash: "#add"
      });
    },
    toDetail(item) {
      const obj = {
        id: null,
        type: null,
        title: null,
        logo: null,
        label: null,
        showLabel: null,
        realname: null
      };
      for (let key in obj) {
        obj[key] = item[key];
      }
      if (item.type == 1) {
        obj.once_money = item.once_money;
        obj.day_money = item.day_money;
      }
      if (item.type == 2) {
        obj.label = this.$store.state.member.info.wx_openid;
        obj.showLabel = this.$store.state.member.info.wx_openid;
      }
      if (item.type == 4) {
        obj.bank_name = item.open_bank;
      }
      this.$router.push({
        name: "property-account-detail",
        query: {
          info: _encode(JSON.stringify(obj))
        }
      });
    }
  },
  components: {
    CellAccountItem
  }
});
</script>

<style scoped>
.list {
  margin-bottom: 80px;
}

.cell {
  padding: 20px;
}
</style>
