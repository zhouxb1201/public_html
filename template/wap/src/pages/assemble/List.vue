<template>
  <div class="assemble-list bg-f8">
    <template v-if="$store.state.config.addons.groupshopping">
      <Navbar />
      <List
        v-model="loading"
        :finished="finished"
        :error.sync="error"
        :is-empty="isListEmpty"
        :empty="{message: '没有相关拼团商品'}"
        @load="loadList"
      >
        <van-cell
          v-for="(item,index) in list"
          :key="index"
          class="item"
          :to="{name:'goods-detail',params:{goodsid:item.goods_id}}"
        >
          <GoodsCard
            :thumb="item.pic_cover_mid | BASESRC"
            :title="item.goods_name"
            :price="item.sku_price.min_price"
          >
            <div slot="tags" class="tags">
              <div>
                已拼
                <span>{{item.goods_total}}</span> 件
              </div>
            </div>
            <div slot="bottomRight" class="foot">
              <div class="user-box">
                <div class="img" v-for="(img,i) in item.user" :key="i">
                  <img :src="img.user_img | BASESRC" />
                </div>
              </div>
            </div>
          </GoodsCard>
        </van-cell>
      </List>
    </template>
    <Empty v-else page-type="fail" message="未开启拼团应用" :show-foot="false" />
  </div>
</template>

<script>
import sfc from "@/utils/create";
import GoodsCard from "@/components/GoodsCard";
import { GET_ASSEMBLELIST } from "@/api/assemble";
import Empty from "@/components/Empty";
import { list } from "@/mixins";
export default sfc({
  name: "assemble-list",
  data() {
    return {};
  },
  mixins: [list],
  mounted() {
    if (this.$route.query.shop_id) {
      this.params.shop_id = this.$route.query.shop_id;
    }
    this.$store.state.config.addons.groupshopping && this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_ASSEMBLELIST($this.params)
        .then(({ data }) => {
          let list = data.group_shopping_list;
          list.map(item => {
            if (item.user.length > 0) {
              item.user = item.user.filter((e, i) => i < 4);
            }
            return item;
          });
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    GoodsCard,
    Empty
  }
});
</script>
<style scoped>
.fw-blod {
  font-weight: 800;
}

.van-card {
  background: #ffffff;
}

.title {
  height: 40px;
}

.list .tags {
  display: flex;
  align-items: center;
}

.list .tags > div {
  color: #ffffff;
  background: #ff454e;
  border-radius: 20px;
  height: 20px;
  padding: 0 10px;
  line-height: 20px;
}

.foot {
  display: flex;
  justify-content: flex-end;
}

.user-box {
  display: flex;
  align-items: center;
}

.user-box .img {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid #ddd;
  margin-left: -8px;
}

.user-box .img img {
  display: block;
  width: 100%;
  height: 100%;
}
</style>
