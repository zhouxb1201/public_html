<template>
  <div class="goods-collection bg-f8">
    <Navbar />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell
        v-for="(item,index) in list"
        :key="index"
        clickable
        :to="{name:'goods-detail',params:{goodsid:item.goods_id}}"
      >
        <div class="item">
          <div class="img">
            <img v-lazy="item.pic_cover" :key="item.pic_cover" pic-type="square" />
          </div>
          <div class="text">
            <div class="name">{{item.goods_name}}</div>
            <van-row type="flex" justify="space-between">
              <van-col class="text-maintone">{{item.price | yuan}}</van-col>
              <van-col class="van-col-icon">
                <van-icon name="like" />
              </van-col>
            </van-row>
          </div>
        </div>
      </van-cell>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import LoadMoreEnd from "@/components/LoadMoreEnd";
import { GET_GOODSCOLLECTLIST } from "@/api/goods";
import { list } from "@/mixins";
export default sfc({
  name: "goods-collection",
  data() {
    return {};
  },
  activated() {
    this.loadList("init");
  },
  mixins: [list],
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_GOODSCOLLECTLIST($this.params)
        .then(({ data }) => {
          let list = data.goods_list;
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  }
});
</script>

<style scoped>
.item {
  display: flex;
}

.item .img {
  width: 64px;
  height: 64px;
  margin-right: 10px;
}

.item .img img {
  display: block;
  width: 100%;
  height: 100%;
}

.item .text {
  flex: 1;
}

.item .text .name {
  height: 40px;
  line-height: 20px;
  word-break: break-all;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.van-col-icon {
  display: flex;
  align-items: center;
}
.van-icon {
  font-size: 16px;
}
</style>
